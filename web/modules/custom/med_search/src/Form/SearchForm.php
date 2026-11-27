<?php

namespace Drupal\med_search\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Htmx\Htmx;
use Drupal\Core\Url;
use Drupal\med_search\Event\SearchCompletedEvent;
use Drupal\med_search\HomepageRouter;
use Drupal\med_search\Render\SearchDebugPanel;
use Drupal\med_search\Render\SearchLinks;
use Drupal\med_search\Render\SearchResultBuilder;
use Drupal\med_search\SearchStrategyInterface;
use Drupal\med_search\SearchStrategyRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * POST-only educational discovery form shared by every search strategy.
 *
 * Five routes use this one form. Each route sets the "strategy" default
 * (semantic, lexical, hybrid or conversational); the homepage also sets
 * "automatic_strategy", so topic phrases get a Hybrid list and questions an
 * answer. Drupal passes route defaults to buildForm() by parameter name.
 */
final class SearchForm extends FormBase {

  use BuildsSearchAnswer;

  public function __construct(
    private SearchStrategyRegistry $strategies,
    private HomepageRouter $router,
    private SearchResultBuilder $results,
    private SearchDebugPanel $debugPanel,
    private EventDispatcherInterface $eventDispatcher,
    private EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('med_search.strategies'),
      $container->get('med_search.homepage_router'),
      $container->get('med_search.result_builder'),
      $container->get('med_search.debug_panel'),
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'med_search';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $strategy
   *   Route default: the strategy ID this page searches with.
   * @param bool $automatic_strategy
   *   Route default (homepage only): route each input with HomepageRouter.
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $strategy = 'semantic', bool $automatic_strategy = FALSE): array {
    // Throws for an unknown ID, so a misconfigured route fails loudly.
    $search = $this->strategies->get($strategy);
    // submitForm() reads these; the build always runs before submission.
    $form_state->set('med_strategy_id', $strategy)->set('med_automatic', $automatic_strategy);
    $form_state->disableCache();
    $form['#cache']['max-age'] = 0;
    $conversational = $strategy === 'conversational';
    $automatic = $automatic_strategy;
    // The strategy that produced the shown results (Hybrid on the homepage
    // for topic phrases).
    $shown = $form_state->getTemporaryValue('med_strategy') ?? $search;
    // Keep the visitor-facing introduction brief.
    $form['intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#attributes' => ['class' => ['med-search__lead']],
      '#value' => $automatic ? $this->t('Search a topic or ask a question about MedlinePlus health information.') : $this->t('Find trusted health information from MedlinePlus.'),
    ];
    $examples = $conversational
      ? [
        $this->t('What is HbA1c?'),
        $this->t('How can I prevent type 2 diabetes?'),
        $this->t('What can trigger a migraine?'),
      ]
      : [
        $this->t('Knee pain when walking'),
        $this->t('Seasonal allergies'),
        $this->t('Lower back pain'),
      ];
    // A chosen example becomes the query text, so keep plain strings.
    $examples = array_map('strval', $examples);
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
      'catalog' => SearchLinks::external([
        '#type' => 'link',
        '#title' => $this->t('Browse MedlinePlus health topics'),
        '#url' => Url::fromUri('https://medlineplus.gov/healthtopics.html'),
      ]),
    ];
    // Progressive enhancement with core HTMX: post in place and swap only
    // this form. Without JavaScript the regular POST and rebuild still work.
    $form['#attributes']['data-med-search'] = $strategy;
    $form['#attributes']['class'][] = 'med-search';
    $form['search_panel']['loading'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#attributes' => [
        'id' => 'med-search-loading',
        'class' => ['med-loading', 'htmx-indicator'],
        'role' => 'status',
        'data-med-loading-mode' => $conversational && !$automatic ? 'answer' : 'search',
      ] + ($automatic ? ['data-med-question-pattern' => HomepageRouter::MED_QUESTION_PATTERN] : []),
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
      $form['howto'] = $this->howItWorks($conversational, $automatic);
    }
    if ($response !== NULL) {
      if (!$browsing && $public_example !== NULL) {
        $form['#attributes']['data-med-public-example'] = $public_example;
      }
      $form['results'] = $this->resultsList($form, $form_state, $response, $shown, $browsing);
    }
    if ($form_state->getTemporaryValue('med_error')) {
      $form['error'] = [
        '#type' => 'container',
        '#attributes' => ['role' => 'alert', 'class' => ['med-state', 'med-state--error']],
        'text' => ['#plain_text' => $this->t('@mode search is temporarily unavailable. Please try again later.', ['@mode' => $shown->label()])],
      ];
    }
    return $form;
  }

  /**
   * The "How it works" steps shown before the first search.
   */
  private function howItWorks(bool $conversational, bool $automatic): array {
    return [
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

  /**
   * Heading, notices, answer, result cards, topic filter and debug panel.
   */
  private function resultsList(array &$form, FormStateInterface $form_state, array $response, SearchStrategyInterface $strategy, bool $browsing): array {
    $results = [
      '#type' => 'container',
      '#attributes' => ['aria-live' => 'polite', 'class' => ['med-results']],
    ];
    $results['toolbar'] = [
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
      $results['notice_' . $delta] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#attributes' => ['role' => 'status', 'class' => ['med-state', 'med-state--notice']],
        'text' => ['#plain_text' => $notice],
      ];
    }
    if (isset($response['answer'])) {
      $results['answer'] = $this->buildAnswer($response['answer'], $response['results']);
      $results['supporting'] = [
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
      $results['toolbar']['view'] = [
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
      $results['empty'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#attributes' => ['class' => ['med-state', 'med-state--empty']],
        '#value' => $this->t('No topics matched closely enough. Try another educational health topic or a different search mode.'),
      ];
    }

    $enrichment_start = hrtime(TRUE);
    [$cards, $facets] = $this->results->cards($response['results'], $strategy, $browsing);
    $results += $cards;
    if ($facets) {
      // The answer and the cards move inside the topic filter container.
      $results['topics'] = $this->results->topicFacet($facets, count($response['results']));
      foreach (array_merge(['answer', 'supporting'], array_keys($cards)) as $key) {
        if (isset($results[$key])) {
          $results['topics']['items'][$key] = $results[$key];
          unset($results[$key]);
        }
      }
    }
    $enrichment_ms = (hrtime(TRUE) - $enrichment_start) / 1e6;

    if (!$browsing) {
      $this->reportOnce($form_state, $response, $enrichment_ms, $strategy);
      if ($this->currentUser()->hasPermission('view med search ranking details')) {
        $results['debug'] = $this->debugPanel->build($response, (float) $form_state->getTemporaryValue('med_search_ms'), $enrichment_ms, $strategy);
      }
    }
    return $results;
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
    ), SearchCompletedEvent::MED_NAME);
  }

  /**
   * Builds a presentation filter option outside the submitted search values.
   */
  private function viewOption(string $value, string|\Stringable $label): array {
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
    $query = trim((string) $form_state->getValue('query'));
    $strategy = $this->strategies->get($form_state->get('med_strategy_id'));
    // Homepage: topic phrases get a Hybrid list. Questions keep the
    // conversational strategy, and urgent wording keeps its fixed response.
    if ($form_state->get('med_automatic') && $this->router->route($query) === HomepageRouter::MED_HYBRID) {
      $strategy = $this->strategies->get('hybrid');
    }
    $form_state->setTemporaryValue('med_strategy', $strategy);
    $start = hrtime(TRUE);
    try {
      $form_state->setTemporaryValue('med_response', $strategy->search($query));
      $form_state->setTemporaryValue('med_search_ms', (hrtime(TRUE) - $start) / 1e6);
    }
    catch (\Throwable) {
      $form_state->setTemporaryValue('med_error', TRUE);
      $this->eventDispatcher->dispatch(new SearchCompletedEvent($strategy->id(), 0, TRUE, NULL, ['total' => (hrtime(TRUE) - $start) / 1e6]), SearchCompletedEvent::MED_NAME);
    }
    // Only temporary state holds results; raw query text is never logged.
    $form_state->disableCache();
    $form_state->setRebuild(TRUE);
  }

}
