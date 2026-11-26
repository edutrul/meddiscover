<?php

namespace Drupal\med_search\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Htmx\Htmx;
use Drupal\Core\Url;
use Drupal\med_search\ResultEnricher;
use Drupal\med_search\SearchStrategyInterface;
use Drupal\med_search\SearchStrategyRegistry;
use Drupal\med_search\StrategyBrand;
use Drupal\med_search\UrgencyDetector;
use Drupal\med_search\Event\SearchCompletedEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * POST-only educational discovery form shared by every search strategy.
 *
 * The route's "strategy" default selects the service; rendering, enrichment
 * and privacy handling are identical for semantic, lexical and hybrid search.
 */
class SearchForm extends FormBase {

  use BuildsSearchAnswer;

  /**
   * Strategy labels and one-line explanations for the interface.
   */
  public const STRATEGIES = [
    'semantic' => [
      'Semantic',
      'Matches meaning using local embeddings, so questions in everyday words can find topics.',
    ],
    'lexical' => [
      'Lexical',
      'Matches your words against topic names, alternative terms, MeSH labels and text, using Apache Solr.',
    ],
    'hybrid' => [
      'Hybrid',
      'Combines the lexical and semantic rankings with Reciprocal Rank Fusion.',
    ],
    'conversational' => [
      'Conversational',
      'Writes a short educational answer using only the retrieved MedlinePlus topics, citing each statement, and declines when the topics do not answer the question.',
    ],
  ];

  /**
   * Question syntax shared with the homepage loading indicator.
   */
  public const QUESTION_PATTERN = '\?$|^(?:what|why|when|where|who|which|how)\s+\S|^(?:can|could|should|do|does|did|is|are|will)\s+(?:i|we|you|it|there|my|the)\b';

  public function __construct(
    private SearchStrategyInterface $search,
    private ResultEnricher $enricher,
    private EventDispatcherInterface $eventDispatcher,
    private EntityTypeManagerInterface $entityTypeManager,
    private ?SearchStrategyRegistry $automaticStrategies = NULL,
    private ?UrgencyDetector $urgency = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    // The route's "strategy" default selects one registered strategy.
    $route = $container->get('current_route_match')->getRouteObject();
    $strategy = $route?->getDefault('strategy') ?? 'semantic';
    if (!isset(self::STRATEGIES[$strategy])) {
      throw new \InvalidArgumentException('Unknown search strategy.');
    }
    return new static(
      $container->get('med_search.strategies')->get($strategy),
      $container->get('med_search.enricher'),
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $route?->getDefault('automatic_strategy') ? $container->get('med_search.strategies') : NULL,
      $container->get('med_search.urgency'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'med_' . $this->search->id() . '_search';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form_state->disableCache();
    $form['#cache']['max-age'] = 0;
    $conversational = $this->search->id() === 'conversational';
    $strategy = $form_state->getTemporaryValue('med_strategy') ?? $this->search;
    $automatic = $this->automaticStrategies !== NULL;
    // Keep the visitor-facing introduction brief.
    $form['intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#attributes' => ['class' => ['med-search__lead']],
      '#value' => $automatic ? $this->t('Search a topic or ask a question about MedlinePlus health information.') : $this->t('Find trusted health information from MedlinePlus.'),
    ];
    $examples = $conversational
      ? ['What is HbA1c?', 'How can I prevent type 2 diabetes?', 'What can trigger a migraine?']
      : ['Knee pain when walking', 'Seasonal allergies', 'Lower back pain'];
    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $conversational && !$automatic ? $this->t('Your health question') : $this->t('Search health topics'),
      '#title_display' => 'invisible',
      '#description' => $this->t('Use your own words. Do not include names or other personal details.'),
      '#maxlength' => 500,
      '#attributes' => [
        'autocomplete' => 'off',
        'placeholder' => $this->t('e.g. @example', ['@example' => $examples[2]]),
        // Native check for an empty box; examples skip it (formnovalidate).
        'required' => 'required',
      ],
    ];
    $storage = $this->entityTypeManager->getStorage('node');
    $ids = $storage->getQuery()->accessCheck(TRUE)
      ->condition('type', 'med_health_topic')->condition('status', 1)
      ->condition('langcode', 'en')->sort('title')->range(0, 100)->execute();
    $titles = [];
    $browse_topics = [];
    foreach ($storage->loadMultiple($ids) as $topic) {
      if ($topic->access('view')) {
        $titles[] = $topic->label();
        $browse_topics[] = ['node' => $topic];
      }
    }
    $form['suggestions'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['med-suggestions'],
        'hidden' => TRUE,
        'data-med-suggestions' => json_encode($titles, JSON_THROW_ON_ERROR),
        'aria-label' => $this->t('Suggested health topics'),
        'role' => 'group',
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $conversational ? $this->t('Ask') : $this->t('Search'),
    ];
    // One-click examples: real submit buttons, so they work without
    // JavaScript and with HTMX alike.
    $form['examples'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['med-examples']],
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#attributes' => ['class' => ['med-examples__label'], 'id' => 'med-examples-label'],
        '#value' => $automatic ? $this->t('Ask a question:') : $this->t('Try an example:'),
      ],
    ];
    $current = trim((string) ($form_state->getUserInput()['query'] ?? ''));
    $public_example = NULL;
    foreach ($examples as $delta => $example) {
      $selected = $current !== '' && mb_strtolower($current) === mb_strtolower($example);
      if ($selected) {
        $public_example = $example;
      }
      $form['examples']['example_' . $delta] = [
        '#type' => 'submit',
        '#name' => 'example_' . $delta,
        '#value' => $example,
        '#med_example' => $example,
        '#attributes' => [
          'class' => $selected ? ['med-examples__button', 'is-selected'] : ['med-examples__button'],
          'aria-describedby' => 'med-examples-label',
          'formnovalidate' => 'formnovalidate',
        ] + ($selected ? ['aria-current' => 'true'] : []),
      ];
    }
    $form['search_panel'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['med-search-panel']],
    ];
    foreach (['intro', 'examples'] as $key) {
      $form['search_panel'][$key] = $form[$key];
      unset($form[$key]);
    }
    $form['search_panel']['query_bar'] = [
      '#type' => 'container',
      '#weight' => -1,
      '#attributes' => ['class' => ['med-query-bar']],
    ];
    if ($automatic) {
      $form['search_panel']['query_bar']['sparkle'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => ['class' => ['med-query-bar__sparkle'], 'aria-hidden' => 'true'],
        '#value' => '✨',
      ];
    }
    $form['search_panel']['intro']['#weight'] = -2;
    foreach (['query', 'submit', 'suggestions'] as $key) {
      $form['search_panel']['query_bar'][$key] = $form[$key];
      unset($form[$key]);
    }
    $form['recovery'] = [
      '#type' => 'container',
      '#weight' => 95,
      '#attributes' => ['class' => ['med-search-recovery']],
      'heading' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Not finding what you’re looking for?'),
      ],
      'suggestion' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Try a shorter phrase or a related topic. For more coverage, browse the full MedlinePlus health topics catalog.'),
      ],
      'catalog' => self::external([
        '#type' => 'link',
        '#title' => $this->t('Browse MedlinePlus health topics'),
        '#url' => Url::fromUri('https://medlineplus.gov/healthtopics.html'),
      ]),
    ];
    // Progressive enhancement with core HTMX: post in place and swap only
    // this form. Without JavaScript the regular POST and rebuild still work.
    $form['#attributes']['data-med-search'] = $this->search->id();
    $form['#attributes']['class'][] = 'med-search';
    $form['search_panel']['loading'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#attributes' => [
        'id' => 'med-search-loading',
        'class' => ['med-loading', 'htmx-indicator'],
        'role' => 'status',
        'data-med-loading-mode' => $conversational && !$automatic ? 'answer' : 'search',
      ] + ($automatic ? ['data-med-question-pattern' => self::QUESTION_PATTERN] : []),
      '#value' => $conversational && !$automatic
        ? $this->t('Writing an answer from the MedlinePlus sources. This usually takes 10 to 20 seconds.')
        : $this->t('Searching…'),
    ];
    $form['request_error'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#attributes' => [
        'class' => ['med-state', 'med-state--error'],
        'role' => 'alert',
        'tabindex' => '-1',
        'hidden' => 'hidden',
        'data-med-search-error' => '',
      ],
      '#value' => $this->t('The search could not be completed. Check your connection and try again.'),
    ];
    (new Htmx())
      ->post(Url::fromRoute('<current>'))
      ->history()
      ->onlyMainContent()
      ->select('form[data-med-search]')
      ->target('this')
      ->swap('outerHTML')
      ->indicator('#med-search-loading')
      ->disabledElt('find [type="submit"]')
      ->applyTo($form);
    $form['#attached']['library'][] = 'med_search/search';
    $response = $form_state->getTemporaryValue('med_response');
    $browsing = $response === NULL && !$form_state->getTemporaryValue('med_error');
    if ($browsing) {
      $response = ['results' => $browse_topics, 'notices' => []];
      $form['howto'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['med-howto']],
        '#weight' => 10,
        'heading' => ['#type' => 'html_tag', '#tag' => 'h2', '#value' => $this->t('How it works')],
        'steps' => [
          '#theme' => 'item_list',
          '#list_type' => 'ol',
          '#items' => [
            $this->t('medDiscover searches 24 MedlinePlus health topics from the National Library of Medicine.'),
            $conversational
              ? ($automatic
                ? $this->t('Topic searches show matching pages. Questions can produce an answer with citations to those pages.')
                : $this->t('The answer uses only those topics, cites each statement, and says so when the topics do not answer your question.'))
              : $this->t('Each result says why it matched and links to the original MedlinePlus page.'),
            $this->t('Next steps point to trusted resources listed by MedlinePlus. Urgent symptoms get emergency guidance first.'),
          ],
        ],
      ];
    }
    if ($response !== NULL) {
      if (!$browsing && $public_example !== NULL) {
        $form['#attributes']['data-med-public-example'] = $public_example;
      }
      $form['results'] = [
        '#type' => 'container',
        '#attributes' => ['aria-live' => 'polite', 'class' => ['med-results']],
      ];
      $form['results']['toolbar'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['med-results__toolbar']],
        '#weight' => -10,
        'heading' => [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#attributes' => ['tabindex' => '-1', 'data-med-results-heading' => '', 'class' => ['med-results__heading']],
          '#value' => $this->formatPlural(count($response['results']), '1 health topic', '@count health topics'),
        ],
      ];
      foreach ($response['notices'] as $delta => $notice) {
        $form['results']['notice_' . $delta] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#attributes' => ['role' => 'status', 'class' => ['med-state', 'med-state--notice']],
          '#value' => $this->t('@notice', ['@notice' => $notice]),
        ];
      }
      if (isset($response['answer'])) {
        $form['results']['answer'] = $this->buildAnswer($response['answer'], $response['results']);
        $form['results']['supporting'] = [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => $this->t('Supporting health topics'),
          '#attributes' => ['data-med-topic-heading' => ''],
        ];
      }
      if (isset($response['answer']) && $response['results']) {
        if ($response['answer']['status'] === 'answered') {
          $form['#attributes']['data-med-default-view'] = 'answer';
        }
        $form['results']['toolbar']['view'] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => ['med-result-filter'],
            'hidden' => TRUE,
            'role' => 'group',
            'aria-label' => $this->t('Result view'),
          ],
          'all' => $this->viewOption('all', $this->t('All')),
          'answer' => $this->viewOption('answer', $this->t('Answer')),
          'topics' => $this->viewOption('topics', $this->t('Topics')),
        ];
      }
      if (!$response['results']) {
        $form['results']['empty'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#attributes' => ['class' => ['med-state', 'med-state--empty']],
          '#value' => $this->t('No topics matched closely enough. Try another educational health topic or a different search mode.'),
        ];
      }
      $facets = [];
      $enrichment_start = hrtime(TRUE);
      foreach ($response['results'] as $delta => $result) {
        $node = $result['node'];
        $groups = [];
        foreach ($node->field_med_groups->referencedEntities() as $term) {
          $groups[$term->id()] = $term;
        }
        foreach ($groups as $id => $group) {
          $facets[$id] ??= ['label' => $group->label(), 'count' => 0];
          $facets[$id]['count']++;
        }
        $enrichment = $browsing ? [] : $this->enricher->enrich($node);
        $form['results'][$delta] = [
          '#theme' => 'med_search_result',
          // For other modules (for example activation): the topic and its
          // already computed enrichment.
          '#med_topic' => $browsing ? NULL : $node,
          '#med_topic_groups' => array_keys($groups),
          '#med_enrichment' => $enrichment,
          'title' => $browsing ? $node->toLink()->toRenderable() : self::track(
            ['#type' => 'link', '#title' => $node->label(), '#url' => $node->toUrl()],
            'search_result_clicked',
            $strategy->id() . ':' . self::rankBucket($delta + 1),
          ),
          'description' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $this->t('@description', ['@description' => $node->field_med_description->value]),
          ],
          // Scores mean nothing to patients and can read like medical
          // confidence; only users who debug or demo ranking see them.
          'score' => !$browsing && $this->currentUser()->hasPermission('view med search ranking details') ? [
            '#type' => 'details',
            '#title' => $this->t('Ranking details'),
            '#attributes' => ['class' => ['med-ranking-details', 'med-debug']],
            'text' => [
              '#type' => 'html_tag',
              '#tag' => 'p',
              '#value' => $this->t('@score', ['@score' => $strategy->describeScore($result)]),
            ],
          ] : [],
          'source' => $node->field_med_source_url->isEmpty() ? [] : self::track(self::external([
            '#type' => 'link',
            '#title' => $this->t('Source: MedlinePlus, National Library of Medicine.'),
            '#url' => Url::fromUri($node->field_med_source_url->uri),
          ]), 'external_resource_clicked', 'source'),
        ] + ($browsing ? [] : $this->buildEnrichment($enrichment));
      }
      if ($facets) {
        $form['results']['topics'] = $this->topicFacet($facets, count($response['results']));
        $items = &$form['results']['topics']['items'];
        foreach (['answer', 'supporting'] as $section) {
          if (isset($form['results'][$section])) {
            $items[$section] = $form['results'][$section];
            unset($form['results'][$section]);
          }
        }
        foreach (array_keys($response['results']) as $delta) {
          $items[$delta] = $form['results'][$delta];
          unset($form['results'][$delta]);
        }
      }
      $enrichment_ms = (hrtime(TRUE) - $enrichment_start) / 1e6;
      if (!$browsing) {
        $this->reportOnce($form_state, $response, $enrichment_ms, $strategy);
        if ($this->currentUser()->hasPermission('view med search ranking details')) {
          $form['results']['debug'] = $this->debugPanel($response, (float) $form_state->getTemporaryValue('med_search_ms'), $enrichment_ms, $strategy);
        }
      }
    }
    $error = $form_state->getTemporaryValue('med_error');
    if ($error) {
      [$label] = self::STRATEGIES[$strategy->id()];
      $form['error'] = [
        '#type' => 'container',
        '#attributes' => ['role' => 'alert', 'class' => ['med-state', 'med-state--error']],
        'text' => ['#plain_text' => $this->t('@mode search is temporarily unavailable. Please try again later.', ['@mode' => $label])],
      ];
    }
    return $form;
  }

  /**
   * Builds a filter for the topics in the returned ranking.
   */
  private function topicFacet(array $facets, int $total): array {
    uasort($facets, fn(array $a, array $b) => strnatcasecmp($a['label'], $b['label']));
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['med-topic-results'], 'data-med-topic-results' => ''],
      'facet' => [
        '#type' => 'html_tag',
        '#tag' => 'details',
        '#attributes' => ['class' => ['med-topic-facet'], 'hidden' => TRUE, 'open' => TRUE],
        'heading' => ['#type' => 'html_tag', '#tag' => 'summary', '#value' => $this->t('Filter by topic')],
      ],
      'items' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['med-topic-results__items']],
      ],
    ];
    $facets = ['all' => ['label' => $this->t('All topics'), 'count' => $total]] + $facets;
    foreach ($facets as $id => $facet) {
      $build['facet']['topic_' . $id] = [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#attributes' => [
          'type' => 'button',
          'data-med-topic-filter' => $id,
          'data-med-topic-label' => $facet['label'],
          'aria-pressed' => $id === 'all' ? 'true' : 'false',
        ],
        '#value' => $this->t('@topic (@count)', ['@topic' => $facet['label'], '@count' => $facet['count']]),
      ];
    }
    return $build;
  }

  /**
   * Render enrichment as three collapsible sections in the existing theme.
   */
  private function buildEnrichment(array $data): array {
    $build = [];
    $info = [];
    foreach (['also_called' => $this->t('Also called'), 'see_reference' => $this->t('See also')] as $key => $label) {
      if ($data[$key]) {
        $info[$key] = $this->itemList($label, array_map(fn($v) => ['#plain_text' => $v], $data[$key]));
      }
    }
    if ($data['groups']) {
      $info['groups'] = $this->itemList($this->t('MedlinePlus groups'), array_map(fn($g) => $this->link($g['label'], $g['url']), $data['groups']));
    }
    if ($data['mesh']) {
      $info['mesh'] = $this->itemList($this->t('MeSH descriptors'), array_map(fn($m) => [
        '#plain_text' => $m['descriptor'] . ($m['qualifier'] ? ' / ' . $m['qualifier'] : ''),
      ], $data['mesh']));
    }
    if ($data['summary_links']) {
      $info['summary_links'] = $this->itemList($this->t('Further reading from the summary'), array_map(fn($l) => self::track($this->link($l['title'], $l['url']), 'external_resource_clicked', 'further_reading'), $data['summary_links']));
    }
    if ($info) {
      $build['info'] = ['#type' => 'details', '#title' => $this->t('Explore Health Information'), '#weight' => 10] + $info;
    }
    if ($data['related']) {
      $items = [];
      foreach ($data['related'] as $item) {
        $items[] = [
          'link' => self::track($item['node'] ? [
            '#type' => 'link',
            '#title' => $item['title'],
            '#url' => $item['node']->toUrl(),
          ] : $this->link($item['title'] . ' (MedlinePlus)', $item['url']), 'related_content_clicked', $item['explicit'] ? 'explicit' : 'inferred'),
          'provenance' => [
            '#type' => 'html_tag',
            '#tag' => 'small',
            '#value' => $this->t('@kind: @why', [
              '@kind' => $item['explicit'] ? $this->t('Explicit') : $this->t('Inferred'),
              '@why' => implode('; ', $item['provenance']),
            ]),
            '#prefix' => ' — ',
          ],
        ];
      }
      $build['related'] = [
        '#type' => 'details',
        '#title' => $this->t('Related Content (@count)', ['@count' => count($items)]),
        '#weight' => 11,
        'list' => ['#theme' => 'item_list', '#items' => $items],
      ];
    }
    if ($data['resources']) {
      $build['resources'] = [
        '#type' => 'details',
        '#title' => $this->t('Explore Healthcare Resources (@count categories)', ['@count' => count($data['resources'])]),
        '#weight' => 12,
      ];
      foreach ($data['resources'] as $category => $resources) {
        $build['resources'][] = $this->itemList($category . ' (' . count($resources) . ')', array_map(fn($r) => [
          'link' => self::track($this->link($r['title'], $r['url']), 'external_resource_clicked', 'resources'),
          'org' => $r['organizations'] ? [
            '#type' => 'html_tag',
            '#tag' => 'small',
            '#value' => $this->t('— @orgs', ['@orgs' => implode(', ', $r['organizations'])]),
            '#prefix' => ' ',
          ] : [],
        ], $resources));
      }
    }
    return $build;
  }

  /**
   * Dispatch the completed-search event once per request.
   */
  private function reportOnce(FormStateInterface $form_state, array $response, float $enrichment_ms, SearchStrategyInterface $strategy): void {
    if ($form_state->getTemporaryValue('med_reported')) {
      return;
    }
    $form_state->setTemporaryValue('med_reported', TRUE);
    $t = $response['timings'];
    $search_ms = (float) $form_state->getTemporaryValue('med_search_ms');
    $timings = array_filter([
      'total' => $search_ms + $enrichment_ms,
      'embedding' => $t['embedding_ms'] ?? NULL,
      'retrieval' => ($t['semantic_ms'] ?? 0) + ($t['lexical_ms'] ?? 0) + ($t['fusion_ms'] ?? 0) ?: NULL,
      // Only measured when there was something to enrich.
      'enrichment' => $response['results'] ? $enrichment_ms : NULL,
      'generation' => $t['generation_ms'] ?? NULL,
    ], fn($v) => $v !== NULL);
    $this->eventDispatcher->dispatch(new SearchCompletedEvent(
      $strategy->id(),
      count($response['results']),
      FALSE,
      $response['answer']['status'] ?? NULL,
      $timings,
    ), SearchCompletedEvent::NAME);
  }

  /**
   * Renders short metric help on hover or as a disclosure.
   */
  public static function metricHelp($label, $help): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'details',
      '#attributes' => ['class' => ['med-metric-help']],
      'summary' => [
        '#type' => 'html_tag',
        '#tag' => 'summary',
        '#value' => $label,
        '#attributes' => ['data-help' => $help],
      ],
      'explanation' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $help,
      ],
    ];
  }

  /**
   * Developer-mode panel: strategy, stage timings and answer status.
   */
  private function debugPanel(array $response, float $search_ms, float $enrichment_ms, SearchStrategyInterface $strategy): array {
    $t = $response['timings'];
    $strategies = [
      'lexical' => $this->t('Use this when you know the term or title. Search for “HbA1c” and Solr looks for that wording in topic titles, alternate names, and other indexed fields.'),
      'semantic' => $this->t('Use this when you can describe what you mean but may not know the official topic name. For “knee pain when walking,” pgvector can bring up “Walking Problems” even though the phrases differ.'),
      'hybrid' => $this->t('Use this for a broad search. medDiscover checks both the words you typed and what they mean. If a knee topic ranks well in both lists, it can move above a topic found by only one approach.'),
      'conversational' => $this->t('Use this when you want a short answer to a question. Try “What is HbA1c?” medDiscover looks up relevant topics, then asks Ollama to write an answer using those sources. Open the citations to check the original information.'),
    ];
    $row = static fn ($label, $value, $help): array => [['data' => self::metricHelp($label, $help)], $value];
    $id = $strategy->id();
    $names = [
      'lexical' => $this->t('Keyword (Lexical)'),
      'semantic' => $this->t('Semantic'),
      'hybrid' => $this->t('Hybrid (Keyword + Semantic)'),
      'conversational' => $this->t('Ask (Conversational)'),
    ];
    $title_id = Html::getUniqueId('med-strategy-dialog-title');
    $dialog = [
      '#type' => 'html_tag',
      '#tag' => 'dialog',
      '#attributes' => ['class' => ['med-strategy-dialog'], 'aria-labelledby' => $title_id],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#attributes' => ['id' => $title_id],
        '#value' => $this->t('How did medDiscover find these results?'),
      ],
      'current' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('This search used @strategy. Here’s when each approach is useful.', ['@strategy' => $names[$id] ?? $id]),
      ],
    ];
    foreach ($strategies as $key => $description) {
      $dialog[$key] = [
        '#type' => 'html_tag',
        '#tag' => 'section',
        'heading' => ['#type' => 'html_tag', '#tag' => 'h3', '#value' => $names[$key]],
        'brand' => StrategyBrand::build($key),
        'body' => ['#type' => 'html_tag', '#tag' => 'p', '#value' => $description],
      ];
    }
    $dialog['close'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#attributes' => ['type' => 'button', 'data-med-strategy-close' => TRUE],
      '#value' => $this->t('Close'),
    ];
    $rows = [[
      [
        'data' => [
          '#type' => 'html_tag',
          '#tag' => 'button',
          '#attributes' => ['type' => 'button', 'data-med-strategy-open' => TRUE, 'aria-haspopup' => 'dialog'],
          '#value' => $this->t('Strategy'),
        ],
      ],
      [
        'data' => [
          '#type' => 'container',
          'active' => ['#plain_text' => (string) ($names[$id] ?? $id)],
          'dialog' => $dialog,
        ],
      ],
    ],
    ];
    $config = $this->config('med_search.settings');
    foreach ([
      'embedding_ms' => [
        $this->t('Query embedding'),
        $this->t('Time for Ollama to turn the query into a vector. The same embedding model was used for the topic vectors stored in PostgreSQL, so pgvector can compare them.'),
      ],
      'lexical_ms' => [
        $this->t('Solr retrieval'),
        $this->t('Time to retrieve and rank topics by keyword matches in Solr. The index contains fields such as titles, alternate names, MeSH terms, and summaries.'),
      ],
      'semantic_ms' => [
        $this->t('pgvector retrieval'),
        $this->t('Time to compare the query vector with stored topic vectors. Similarity is 1 minus cosine distance, so a distance of 0.26 gives a similarity of 0.74. The semantic search applies a configured minimum similarity.'),
      ],
      'fusion_ms' => [
        $this->t('RRF fusion'),
        $this->t('Time to merge the keyword and semantic rankings. A topic earns 1 / (k + rank) for each list where it appears. With k = 60, ranks 1 and 5 contribute 1/61 + 1/65.'),
      ],
      'generation_ms' => [
        $this->t('LLM generation'),
        $this->t('Time for the answer model to read the retrieved source excerpts and attempt a cited response. This stage only runs when conversational search has sources to send.'),
      ],
    ] as $key => [$label, $help]) {
      if (!isset($t[$key])) {
        continue;
      }
      $rows[] = $row($label, number_format($t[$key], 1) . ' ms', $help);
      if (in_array($key, ['embedding_ms', 'generation_ms'], TRUE)) {
        $embedding = $key === 'embedding_ms';
        $model = (string) $config->get($embedding ? 'model' : 'generation_model');
        $family = str_starts_with($model, 'meddiscover-qwen:') ? 'qwen2.5' : explode(':', $model, 2)[0];
        $library = in_array($family, ['nomic-embed-text', 'gemma3', 'qwen2.5'], TRUE) ? '/' . $family : '';
        $rows[] = $row(
          $embedding ? $this->t('Embedding model') : $this->t('Answer model'),
          [
            'data' => [
              '#type' => 'link',
              '#title' => $this->t('Ollama · @model', ['@model' => $model]),
              '#url' => Url::fromUri('https://ollama.com/library' . $library),
            ],
          ],
          $embedding ? $this->t('Configured model for query embeddings. The link opens its Ollama library page.') : $this->t('Configured model for answer generation. The link opens the Ollama library for its model family.'),
        );
      }
    }
    $rows[] = $row($this->t('Enrichment (all results)'), number_format($enrichment_ms, 1) . ' ms', $this->t('Time to look up and attach details to the results after ranking, including related topics, terminology, and resource links. It covers all returned topics.'));
    $rows[] = $row($this->t('Total server time'), number_format($search_ms + $enrichment_ms, 1) . ' ms', $this->t('Search and enrichment time measured on the server. Browser and network time are separate.'));
    $rows[] = $row($this->t('Results shown'), count($response['results']), $this->t('Number of topics returned by the search before any display filter.'));
    if (isset($response['answer'])) {
      $status = $response['answer']['status'];
      if (isset($response['answer']['reason'])) {
        $status .= ' (' . $response['answer']['reason'] . ')';
      }
      $rows[] = $row($this->t('Answer status'), $status, $this->t('Whether a cited answer was produced, skipped for insufficient evidence, withheld, or unavailable.'));
    }
    return [
      '#type' => 'details',
      '#title' => $this->t('Developer details for this search'),
      '#open' => TRUE,
      '#weight' => -50,
      '#attributes' => ['class' => ['med-debug', 'med-debug-panel']],
      'table' => ['#type' => 'table', '#rows' => $rows],
    ];
  }

  /**
   * Mark a link for aggregate click counting (inert without med_analytics).
   */
  public static function track(array $link, string $event, string $dimension): array {
    $link['#attributes']['data-med-event'] = $event;
    $link['#attributes']['data-med-dimension'] = $dimension;
    return $link;
  }

  /**
   * Coarse rank bucket, so clicks never identify a specific result.
   */
  public static function rankBucket(int $rank): string {
    return $rank === 1 ? 'rank1' : ($rank <= 3 ? 'rank2-3' : 'rank4plus');
  }

  /**
   * External link to an original source URL.
   */
  private function link(string $title, string $url): array {
    return self::external(['#type' => 'link', '#title' => $title, '#url' => Url::fromUri($url)]);
  }

  /**
   * Mark an off-site link: icon via the theme, hidden text for screen readers.
   */
  public static function external(array $link): array {
    $link['#attributes']['class'][] = 'med-link--external';
    $link['#attributes']['rel'] = 'noopener noreferrer';
    $link['#attributes']['target'] = '_blank';
    $link['#suffix'] = '<span class="visually-hidden"> ' . t('(opens in a new tab)') . '</span>' . ($link['#suffix'] ?? '');
    return $link;
  }

  /**
   * Builds a presentation filter option outside the submitted search values.
   */
  private function viewOption(string $value, string $label): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#attributes' => [
        'type' => 'button',
        'data-med-result-view' => $value,
        'aria-pressed' => $value === 'all' ? 'true' : 'false',
      ],
      '#value' => $label,
    ];
  }

  /**
   * Titled item list.
   */
  private function itemList($title, array $items): array {
    return ['#theme' => 'item_list', '#title' => $title, '#items' => $items];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $example = $form_state->getTriggeringElement()['#med_example'] ?? NULL;
    if ($example !== NULL) {
      $form_state->setValue('query', $example);
      // Show the chosen example in the box after the rebuild.
      $input = $form_state->getUserInput();
      $input['query'] = $example;
      $form_state->setUserInput($input);
    }
    elseif (trim((string) $form_state->getValue('query')) === '') {
      $form_state->setErrorByName('query', $this->t('Enter a health question or choose an example.'));
    }
  }

  /**
   * Determines whether the homepage returns a hybrid list for this input.
   */

  /**
   * Explains which part of QUESTION_PATTERN the query matched, if any.
   *
   * Kept next to the pattern so the explanation cannot drift from the rule.
   *
   * @return string|null
   *   A phrase such as "starts with the question word “what”", or NULL when
   *   the query is not a question.
   */
  public static function questionReason(string $query): ?string {
    $query = trim($query);
    if (preg_match('/\?$/u', $query)) {
      return (string) t('ends with a question mark');
    }
    if (preg_match('/^(what|why|when|where|who|which|how)\s+\S/iu', $query, $match)) {
      return (string) t('starts with the question word “@word”', ['@word' => mb_strtolower($match[1])]);
    }
    if (preg_match('/^(can|could|should|do|does|did|is|are|will)\s+(i|we|you|it|there|my|the)\b/iu', $query, $match)) {
      return (string) t('starts with “@words”, a question opener', ['@words' => mb_strtolower($match[1] . ' ' . $match[2])]);
    }
    return NULL;
  }

  /**
   * Whether the homepage sends this query to Hybrid (topic search).
   */
  public static function usesHomepageHybrid(string $query, ?string $urgency): bool {
    return $urgency === NULL && !preg_match('/' . self::QUESTION_PATTERN . '/iu', $query);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $query = trim((string) $form_state->getValue('query'));
    $strategy = $this->search;
    // Always preserve the fixed urgent response, including terse queries.
    if ($this->automaticStrategies && self::usesHomepageHybrid($query, $this->urgency?->detect($query))) {
      $strategy = $this->automaticStrategies->get('hybrid');
    }
    $form_state->setTemporaryValue('med_strategy', $strategy);
    $start = hrtime(TRUE);
    try {
      $form_state->setTemporaryValue('med_response', $strategy->search($query));
      $form_state->setTemporaryValue('med_search_ms', (hrtime(TRUE) - $start) / 1e6);
    }
    catch (\Throwable) {
      $form_state->setTemporaryValue('med_error', TRUE);
      $this->eventDispatcher->dispatch(new SearchCompletedEvent($strategy->id(), 0, TRUE, NULL, ['total' => (hrtime(TRUE) - $start) / 1e6]), SearchCompletedEvent::NAME);
    }
    // Only temporary state holds results; raw query text is never logged.
    $form_state->disableCache();
    $form_state->setRebuild(TRUE);
  }

}
