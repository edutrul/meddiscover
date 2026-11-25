<?php

namespace Drupal\med_search\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Htmx\Htmx;
use Drupal\Core\Url;
use Drupal\med_search\ResultEnricher;
use Drupal\med_search\SearchStrategyInterface;
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

  public function __construct(
    private SearchStrategyInterface $search,
    private ResultEnricher $enricher,
    private EventDispatcherInterface $eventDispatcher,
    private EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    // The route's "strategy" default selects one registered strategy.
    $strategy = $container->get('current_route_match')->getRouteObject()?->getDefault('strategy') ?? 'semantic';
    if (!isset(self::STRATEGIES[$strategy])) {
      throw new \InvalidArgumentException('Unknown search strategy.');
    }
    return new static(
      $container->get('med_search.strategies')->get($strategy),
      $container->get('med_search.enricher'),
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
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
    // Keep the visitor-facing introduction brief.
    $form['intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#attributes' => ['class' => ['med-search__lead']],
      '#value' => $this->t('Find trusted health information from MedlinePlus.'),
    ];
    $examples = $conversational
      ? ['What is HbA1c?', 'How can I prevent type 2 diabetes?', 'What can trigger a migraine?']
      : ['Knee pain when walking', 'Seasonal allergies', 'Lower back pain'];
    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $conversational ? $this->t('Your health question') : $this->t('Search health topics'),
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
    foreach ($storage->loadMultiple($ids) as $topic) {
      if ($topic->access('view')) {
        $titles[] = $topic->label();
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
        '#value' => $this->t('Try an example:'),
      ],
    ];
    $current = trim((string) ($form_state->getUserInput()['query'] ?? ''));
    foreach ($examples as $delta => $example) {
      $selected = $current !== '' && mb_strtolower($current) === mb_strtolower($example);
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
      '#attributes' => ['id' => 'med-search-loading', 'class' => ['med-loading', 'htmx-indicator'], 'role' => 'status'],
      '#value' => $this->search->id() === 'conversational'
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
    if ($response === NULL && !$form_state->getTemporaryValue('med_error')) {
      $form['howto'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['med-howto']],
        'heading' => ['#type' => 'html_tag', '#tag' => 'h2', '#value' => $this->t('How it works')],
        'steps' => [
          '#theme' => 'item_list',
          '#list_type' => 'ol',
          '#items' => [
            $this->t('medDiscover searches 24 MedlinePlus health topics from the National Library of Medicine.'),
            $conversational
              ? $this->t('The answer uses only those topics, cites each statement, and says so when the topics do not answer your question.')
              : $this->t('Each result says why it matched and links to the original MedlinePlus page.'),
            $this->t('Next steps point to trusted resources listed by MedlinePlus. Urgent symptoms get emergency guidance first.'),
          ],
        ],
      ];
    }
    if ($response !== NULL) {
      $form['results'] = [
        '#type' => 'container',
        '#attributes' => ['aria-live' => 'polite', 'class' => ['med-results']],
      ];
      $form['results']['heading'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#attributes' => ['tabindex' => '-1', 'data-med-results-heading' => '', 'class' => ['med-results__heading']],
        '#value' => $this->t('Search results'),
      ];
      $form['results']['compare'] = [
        '#type' => 'link',
        '#title' => $this->t('Compare search modes'),
        '#url' => Url::fromRoute('med_search.compare'),
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
        $form['results']['view'] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => ['med-result-filter'],
            'hidden' => TRUE,
            'role' => 'group',
            'aria-label' => $this->t('Result view'),
          ],
          'all' => $this->viewOption('all', $this->t('All results')),
          'answer' => $this->viewOption('answer', $this->t('Answer')),
          'topics' => $this->viewOption('topics', $this->t('Health topics')),
          '#weight' => -2,
        ];
        $form['results']['heading']['#weight'] = -3;
        $form['results']['compare']['#weight'] = -1;
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
        $enrichment = $this->enricher->enrich($node);
        $form['results'][$delta] = [
          '#theme' => 'med_search_result',
          // For other modules (for example activation): the topic and its
          // already computed enrichment.
          '#med_topic' => $node,
          '#med_topic_groups' => array_keys($groups),
          '#med_enrichment' => $enrichment,
          'title' => self::track(
            ['#type' => 'link', '#title' => $node->label(), '#url' => $node->toUrl()],
            'search_result_clicked',
            $this->search->id() . ':' . self::rankBucket($delta + 1),
          ),
          'description' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $this->t('@description', ['@description' => $node->field_med_description->value]),
          ],
          // Scores mean nothing to patients and can read like medical
          // confidence; only users who debug or demo ranking see them.
          'score' => $this->currentUser()->hasPermission('view med search ranking details') ? [
            '#type' => 'details',
            '#title' => $this->t('Ranking details'),
            '#attributes' => ['class' => ['med-ranking-details', 'med-debug']],
            'text' => [
              '#type' => 'html_tag',
              '#tag' => 'p',
              '#value' => $this->t('@score', ['@score' => $this->search->describeScore($result)]),
            ],
          ] : [],
          'source' => self::track(self::external([
            '#type' => 'link',
            '#title' => $this->t('Source: MedlinePlus, National Library of Medicine.'),
            '#url' => Url::fromUri($node->field_med_source_url->uri),
          ]), 'external_resource_clicked', 'source'),
        ] + $this->buildEnrichment($enrichment);
      }
      if ($facets) {
        $form['results']['topics'] = $this->topicFacet($facets, count($response['results']));
        $items = &$form['results']['topics']['items'];
        if (isset($form['results']['supporting'])) {
          $items['supporting'] = $form['results']['supporting'];
          unset($form['results']['supporting']);
        }
        foreach (array_keys($response['results']) as $delta) {
          $items[$delta] = $form['results'][$delta];
          unset($form['results'][$delta]);
        }
      }
      $enrichment_ms = (hrtime(TRUE) - $enrichment_start) / 1e6;
      $this->reportOnce($form_state, $response, $enrichment_ms);
      if ($this->currentUser()->hasPermission('view med search ranking details')) {
        $form['results']['debug'] = $this->debugPanel($response, (float) $form_state->getTemporaryValue('med_search_ms'), $enrichment_ms);
      }
    }
    $error = $form_state->getTemporaryValue('med_error');
    if ($error) {
      [$label] = self::STRATEGIES[$this->search->id()];
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
          'aria-pressed' => $id === 'all' ? 'true' : 'false',
        ],
        '#value' => $this->t('@topic (@count)', ['@topic' => $facet['label'], '@count' => $facet['count']]),
      ];
    }
    return $build;
  }

  /**
   * Render a conversational answer with numbered, verifiable citations.
   */
  private function buildAnswer(array $answer, array $results): array {
    $build = ['#type' => 'container', '#attributes' => ['class' => ['med-answer', 'med-answer--' . $answer['status']]]];
    $nodes = array_column($results, 'node', 'id');
    switch ($answer['status']) {
      case 'urgent':
        $build['message'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#attributes' => ['role' => 'alert'],
          '#value' => '<strong>' . $this->t('@message', ['@message' => UrgencyDetector::message($answer['type'])]) . '</strong>',
        ];
        return $build;

      case 'answered':
        $numbers = [];
        $items = [];
        foreach ($answer['statements'] as $statement) {
          $refs = [];
          foreach ($statement['citations'] as $id) {
            $numbers[$id] ??= count($numbers) + 1;
            $refs[] = '[' . $numbers[$id] . ']';
          }
          $items[] = ['#plain_text' => $statement['text'] . ' ' . implode('', $refs)];
        }
        $build['heading'] = ['#type' => 'html_tag', '#tag' => 'h3', '#value' => $this->t('Educational answer')];
        $build['statements'] = ['#theme' => 'item_list', '#items' => $items];
        $sources = [];
        foreach ($numbers as $id => $number) {
          $node = $nodes[$id];
          $sources[] = [
            'topic' => [
              '#type' => 'link',
              '#title' => $node->label(),
              '#url' => $node->toUrl(),
              '#prefix' => '[' . $number . '] ',
            ],
            'origin' => self::track(self::external([
              '#prefix' => ' — ',
              '#type' => 'link',
              '#title' => $this->t('MedlinePlus, National Library of Medicine'),
              '#url' => Url::fromUri($node->field_med_source_url->uri),
            ]), 'external_resource_clicked', 'citation'),
          ];
        }
        $build['sources'] = ['#theme' => 'item_list', '#title' => $this->t('Sources'), '#items' => $sources];
        $build['limits'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('Generated by a local language model from the sources above. It can be incomplete or wrong, it is not a diagnosis or medical advice, and it does not replace a health professional. Check the cited topics.'),
        ];
        return $build;

      case 'abstained':
        $text = $answer['reason'] === 'no_evidence'
          ? $this->t('No MedlinePlus topic in this collection matches your question, so no answer was written.')
          : $this->t('The matching topics do not answer this question, so no answer was written. The closest topics are listed below.');
        break;

      case 'withheld':
        $text = $this->t('An answer was generated but did not pass the citation and safety checks, so it is not shown. The closest topics are listed below.');
        break;

      default:
        $text = $this->t('An answer could not be generated right now.');
    }
    $build['message'] = ['#type' => 'html_tag', '#tag' => 'p', '#attributes' => ['role' => 'status'], '#value' => $text];
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
  private function reportOnce(FormStateInterface $form_state, array $response, float $enrichment_ms): void {
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
      $this->search->id(),
      count($response['results']),
      FALSE,
      $response['answer']['status'] ?? NULL,
      $timings,
    ), SearchCompletedEvent::NAME);
  }

  /**
   * Developer-mode panel: strategy, stage timings and answer status.
   */
  private function debugPanel(array $response, float $search_ms, float $enrichment_ms): array {
    $t = $response['timings'];
    $rows = [[$this->t('Strategy'), $this->search->id()]];
    foreach ([
      'embedding_ms' => $this->t('Query embedding'),
      'lexical_ms' => $this->t('Solr retrieval'),
      'semantic_ms' => $this->t('pgvector retrieval'),
      'fusion_ms' => $this->t('RRF fusion'),
      'generation_ms' => $this->t('LLM generation'),
    ] as $key => $label) {
      if (isset($t[$key])) {
        $rows[] = [$label, number_format($t[$key], 1) . ' ms'];
      }
    }
    $rows[] = [$this->t('Enrichment (all results)'), number_format($enrichment_ms, 1) . ' ms'];
    $rows[] = [$this->t('Total server time'), number_format($search_ms + $enrichment_ms, 1) . ' ms'];
    $rows[] = [$this->t('Results shown'), count($response['results'])];
    if (isset($response['answer'])) {
      $status = $response['answer']['status'];
      if (isset($response['answer']['reason'])) {
        $status .= ' (' . $response['answer']['reason'] . ')';
      }
      $rows[] = [$this->t('Answer status'), $status];
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $start = hrtime(TRUE);
    try {
      $form_state->setTemporaryValue('med_response', $this->search->search((string) $form_state->getValue('query')));
      $form_state->setTemporaryValue('med_search_ms', (hrtime(TRUE) - $start) / 1e6);
    }
    catch (\Throwable) {
      $form_state->setTemporaryValue('med_error', TRUE);
      $this->eventDispatcher->dispatch(new SearchCompletedEvent($this->search->id(), 0, TRUE, NULL, ['total' => (hrtime(TRUE) - $start) / 1e6]), SearchCompletedEvent::NAME);
    }
    // Only temporary state holds results; raw query text is never logged.
    $form_state->disableCache();
    $form_state->setRebuild(TRUE);
  }

}
