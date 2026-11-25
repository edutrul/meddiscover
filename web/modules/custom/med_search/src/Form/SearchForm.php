<?php

namespace Drupal\med_search\Form;

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
    [$label, $explanation] = self::STRATEGIES[$this->search->id()];
    $modes = [];
    foreach (self::STRATEGIES as $id => [$mode]) {
      $modes[] = $id === $this->search->id()
        ? ['#type' => 'html_tag', '#tag' => 'strong', '#value' => $mode]
        : ['#type' => 'link', '#title' => $mode, '#url' => Url::fromRoute('med_search.' . $id)];
    }
    $form['modes'] = [
      '#theme' => 'item_list',
      '#title' => $this->t('Search mode'),
      '#items' => $modes,
      '#attributes' => ['class' => ['inline']],
    ];
    $form['intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('@explanation Explore educational health topics. This proof of concept does not provide diagnosis or personalized medical advice.', ['@explanation' => $explanation]),
    ];
    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search health information'),
      '#required' => TRUE,
      '#maxlength' => 500,
      '#attributes' => ['autocomplete' => 'off'],
    ];
    $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Search')];
    // Progressive enhancement with core HTMX: post in place and swap only
    // this form. Without JavaScript the regular POST and rebuild still work.
    $form['#attributes']['data-med-search'] = $this->search->id();
    $form['#attributes']['class'][] = 'med-search';
    $form['loading'] = [
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
      ->onlyMainContent()
      ->select('form[data-med-search]')
      ->target('this')
      ->swap('outerHTML')
      ->indicator('#med-search-loading')
      ->disabledElt('find [type="submit"]')
      ->applyTo($form);
    $form['#attached']['library'][] = 'med_search/search';
    $response = $form_state->getTemporaryValue('med_response');
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
      $enrichment_start = hrtime(TRUE);
      foreach ($response['results'] as $delta => $result) {
        $node = $result['node'];
        $enrichment = $this->enricher->enrich($node);
        $form['results'][$delta] = [
          '#theme' => 'med_search_result',
          // For other modules (for example activation): the topic and its
          // already computed enrichment.
          '#med_topic' => $node,
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
          'score' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $this->t('@score', ['@score' => $this->search->describeScore($result)]),
          ],
          'source' => self::track([
            '#type' => 'link',
            '#title' => $this->t('Source: MedlinePlus, National Library of Medicine.'),
            '#url' => Url::fromUri($node->field_med_source_url->uri),
          ], 'external_resource_clicked', 'source'),
        ] + $this->buildEnrichment($enrichment);
      }
      $this->reportOnce($form_state, $response, (hrtime(TRUE) - $enrichment_start) / 1e6);
    }
    $error = $form_state->getTemporaryValue('med_error');
    if ($error) {
      $form['error'] = [
        '#type' => 'container',
        '#attributes' => ['role' => 'alert', 'class' => ['med-state', 'med-state--error']],
        'text' => ['#plain_text' => $this->t('@mode search is temporarily unavailable. Please try again later.', ['@mode' => $label])],
      ];
    }
    return $form;
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
            'origin' => self::track([
              '#prefix' => ' — ',
              '#type' => 'link',
              '#title' => $this->t('MedlinePlus, National Library of Medicine'),
              '#url' => Url::fromUri($node->field_med_source_url->uri),
            ], 'external_resource_clicked', 'citation'),
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
    return ['#type' => 'link', '#title' => $title, '#url' => Url::fromUri($url)];
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
