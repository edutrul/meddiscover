<?php

namespace Drupal\med_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Htmx\Htmx;
use Drupal\Core\Url;
use Drupal\med_search\ConversationalSearch;
use Drupal\med_search\GeneratorInterface;
use Drupal\med_search\SearchStrategyRegistry;
use Drupal\med_search\StrategyBrand;
use Drupal\med_search\HomepageRouter;
use Drupal\med_search\Render\CompareVerdictBuilder;
use Drupal\med_search\Render\MetricHelp;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lets reviewers run the same query through each retrieval strategy.
 */
final class CompareSearchForm extends FormBase {

  use BuildsSearchAnswer;

  private const MED_BASELINE_QUESTION = 'What is HbA1c?';
  private const MED_BASELINE_INSTRUCTIONS = 'Answer the fixed educational question briefly in plain language, using only your general knowledge. Give a definition. Do not give personal advice, treatment, or citations. If unsure, return an empty answer.';

  public function __construct(
    private SearchStrategyRegistry $strategies,
    private HomepageRouter $router,
    private GeneratorInterface $generator,
    private StrategyBrand $brand,
    private CompareVerdictBuilder $verdictBuilder,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('med_search.strategies'), $container->get('med_search.homepage_router'), $container->get('med_search.generator'), $container->get('med_search.strategy_brand'), $container->get('med_search.compare_verdict'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'med_compare_search';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form_state->disableCache();
    $form['#cache']['max-age'] = 0;
    $form['#attributes']['data-med-search'] = 'compare';
    $form['#attributes']['class'][] = 'med-compare';
    $form['#attached']['library'][] = 'med_search/search';
    (new Htmx())->post(Url::fromRoute('med_search.compare'))->history()
      ->onlyMainContent()->select('form[data-med-search="compare"]')
      ->target('this')->swap('outerHTML')->indicator('#med-compare-loading')
      ->disabledElt('find [type="submit"]')->applyTo($form);

    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Query to compare'),
      '#description' => $this->t('Use a demo query or enter your own words without personal details.'),
      '#maxlength' => 500,
      '#attributes' => ['autocomplete' => 'off'],
    ];
    $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Compare')];
    $form['examples'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['med-examples']],
    ];
    $form['examples']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => ['class' => ['med-examples__label']],
      '#value' => $this->t('Try one of these searches'),
    ];
    $examples = [
      $this->t('HbA1c'),
      $this->t('Backache'),
      $this->t('Knee pain when walking'),
      $this->t('What is HbA1c?'),
    ];
    foreach (array_map('strval', $examples) as $index => $example) {
      $form['examples'][$index] = [
        '#type' => 'submit',
        '#name' => 'compare_example_' . $index,
        '#value' => $example,
        '#med_example' => $example,
      ];
    }

    $form['loading'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#attributes' => [
        'id' => 'med-compare-loading',
        'class' => ['med-loading'],
        'role' => 'status',
        // Questions also get an answer, which takes much longer.
        'data-med-question-pattern' => HomepageRouter::MED_QUESTION_PATTERN,
        'data-med-loading-answer' => (string) $this->t('Running the three searches and writing a cited answer from MedlinePlus… this can take 10–40 seconds.'),
        'data-med-loading-search' => (string) $this->t('Running all three searches…'),
      ],
      '#value' => $this->t('Running all three searches…'),
    ];

    $response = $form_state->getTemporaryValue('comparison');
    if ($response === NULL) {
      return $form;
    }
    if ($example = $form_state->getTriggeringElement()['#med_example'] ?? NULL) {
      $form['#attributes']['data-med-public-example'] = $example;
    }
    $form['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Comparison results'),
      '#attributes' => ['tabindex' => '-1', 'data-med-results-heading' => ''],
    ];
    $query = trim((string) $form_state->getValue('query'));
    $config = $this->config('med_search.settings');
    $retrieval_id = $config->get('rag_strategy');
    $retrieval = match ($retrieval_id) {
      'lexical' => $this->t('Keyword (Lexical)'),
      'hybrid' => $this->t('Hybrid'),
      default => $this->t('Semantic'),
    };
    $route = $this->router->route($query);
    $uses_hybrid = $route === HomepageRouter::MED_HYBRID;
    $uses_conversation = $route === HomepageRouter::MED_ASK;
    // With no topic at all, the homepage would show its "no topics" message,
    // so no card earns the ribbon.
    $nothing_found = $uses_hybrid && empty($response['hybrid']['results']);
    $ribbon_hybrid = $uses_hybrid && !$nothing_found;
    // The main point of this page: what the homepage would answer, and why.
    $form['verdict'] = $this->verdictBuilder->build($query, $response, $form_state->getTemporaryValue('comparison_answer'), $retrieval);
    if (!$uses_conversation) {
      $form['evidence_heading'] = $this->evidenceHeading();
    }
    $form['ranking'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['med-comparison-grid']],
    ];
    $can_debug = $this->currentUser()->hasPermission('view med search ranking details');
    $methods = [
      'lexical' => $this->t('Keyword (Lexical)'),
      'semantic' => $this->t('Semantic'),
      'hybrid' => $this->t('Hybrid (Keyword + Semantic)'),
    ];
    foreach ($methods as $id => $label) {
      $column = [
        '#type' => 'html_tag',
        '#tag' => 'section',
        '#attributes' => $ribbon_hybrid && $id === 'hybrid' ? $this->homepageCard() : [],
        'corner' => $ribbon_hybrid && $id === 'hybrid' ? $this->cornerRibbon() : [],
        'heading' => ['#type' => 'html_tag', '#tag' => 'h3', '#value' => $label],
        'ribbon' => $uses_conversation && $id === $retrieval_id
          ? $this->homepageRibbon($this->t('Finds sources for the answer'))
          : [],
        'brand' => $this->brand->build($id),
        'about' => ['#plain_text' => $this->strategies->get($id)->description()],
      ];
      if ($id === 'semantic') {
        $column['threshold'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('Similarity cutoff: @threshold. Hybrid applies it to Semantic matches only.', [
            '@threshold' => (string) $config->get('threshold'),
          ]),
        ];
      }
      $ranking_help = match ($id) {
        'lexical' => $this->t('Solr scores matches across indexed fields. Strongly weighted fields, such as the title, can raise a topic’s position. Use the score to compare topics within this query.'),
        'semantic' => $this->t('Cosine similarity measures how close the query and topic vectors are: 1 minus cosine distance. A higher score ranks first among topics that pass the cutoff.'),
        'hybrid' => $this->t('Reciprocal Rank Fusion (RRF) adds points from each list. First place earns 1/(@k + 1), second earns 1/(@k + 2), and an absent topic earns zero from that list. A topic ranked first in Keyword and second in Semantic earns both amounts. The highest total ranks first.', [
          '@k' => (int) $config->get('hybrid_rrf_k'),
        ]),
      };
      $column['ranking_help'] = MetricHelp::build($this->t('How ranking works'), $ranking_help);
      if ($ribbon_hybrid && $id === 'hybrid') {
        $column['why'] = $this->whyThisOne($this->t('For topic searches the homepage wants the most complete list. Keyword catches exact names and aliases such as “HbA1c”, and Semantic catches related meaning. In our tests, Hybrid found the most relevant topics: 96% of them, against 90% for Semantic and 79% for Keyword.'));
      }
      $result = $response[$id];
      if ($result === NULL || !$result['results']) {
        $column['status'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $result === NULL ? $this->t('This strategy is temporarily unavailable.') : $this->t('No matching topics.'),
        ];
      }
      else {
        $strategy = $this->strategies->get($id);
        $column['topics'] = ['#theme' => 'item_list', '#list_type' => 'ol', '#items' => []];
        foreach ($result['results'] as $item) {
          $entry = ['topic' => $item['node']->toLink()->toRenderable()];
          if ($can_debug) {
            $entry['score'] = [
              '#type' => 'container',
              '#attributes' => ['class' => ['med-debug']],
              'value' => ['#plain_text' => $strategy->describeScore($item)],
            ];
          }
          $column['topics']['#items'][] = $entry;
        }
        if ($can_debug) {
          $column['timing'] = [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#attributes' => ['class' => ['med-debug']],
            '#value' => $this->t('Elapsed server time: @ms ms', ['@ms' => number_format($result['comparison_ms'], 1)]),
          ];
        }
      }
      if ($id === 'semantic' && $can_debug && isset($result['embedding_debug'])) {
        $debug = $result['embedding_debug'];
        $debug['pgvector_results'] = array_map(static fn(array $item): array => [
          'title' => $item['node']->label(),
          'cosine_similarity' => $item['score'],
        ], $result['results']);
        $column['embedding_debug'] = $this->debugCode($this->t('Ollama embedding and pgvector results'), $debug);
      }
      if ($id === 'hybrid' && $can_debug && isset($result['hybrid_debug'])) {
        $column['hybrid_debug'] = $this->debugCode($this->t('Hybrid rankings and RRF scores'), $result['hybrid_debug']);
      }
      if ($id === 'lexical' && $can_debug && isset($result['solr_debug'])) {
        $column['solr_debug'] = $this->debugCode($this->t('Solr query and results'), $result['solr_debug']);
      }
      foreach ($result['notices'] ?? [] as $index => $notice) {
        $column['notice_' . $index] = ['#plain_text' => $notice];
      }
      $form['ranking'][$id] = $column;
    }
    if ($uses_conversation) {
      $answer = $form_state->getTemporaryValue('comparison_answer');
      $form['conversation'] = [
        '#type' => 'container',
        '#attributes' => $this->homepageCard(['med-conversation-card']),
        'corner' => $this->cornerRibbon(),
        'heading' => ['#type' => 'html_tag', '#tag' => 'h2', '#value' => $this->t('Ask (Conversational)')],
        'description' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('@strategy search finds the topics. Ollama reads excerpts from those topics and writes an answer with citations.', ['@strategy' => $retrieval]),
        ],
        'why' => $this->whyThisOne($this->t('A question needs an answer, not a list, and every source found is handed to the AI, so precision matters more than completeness. In our tests, Semantic search ignored all 10 unrelated questions, while Keyword, and Hybrid through it, can be misled by words like “how do I”. Semantic sources also produced the best answers.')),
      ];

      if ($answer !== NULL) {
        $form['conversation']['outputs'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['med-answer-comparison']],
        ];
        if ($query === self::MED_BASELINE_QUESTION) {
          $form['conversation']['outputs']['baseline'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['med-answer']],
            'heading' => ['#type' => 'html_tag', '#tag' => 'h3', '#value' => $this->t('Without search')],
            'note' => [
              '#type' => 'html_tag',
              '#tag' => 'p',
              '#value' => $this->t('The same model answers from memory, without MedlinePlus excerpts or citations.'),
            ],
            'text' => [
              '#type' => 'html_tag',
              '#tag' => 'p',
              'value' => ['#plain_text' => $answer['baseline'] ?? $this->t('The model did not return a usable answer without search.')],
            ],
          ];
          if ($can_debug && isset($answer['baseline'])) {
            $form['conversation']['outputs']['baseline']['prompt'] = $this->debugCode($this->t('Ollama prompt without search'), [
              'model' => $this->generator->modelId(),
              'system' => self::MED_BASELINE_INSTRUCTIONS,
              'user' => self::MED_BASELINE_QUESTION,
            ]);
          }
        }
        $form['conversation']['outputs']['grounded'] = [
          '#type' => 'container',
          'heading' => ['#type' => 'html_tag', '#tag' => 'h3', '#value' => $this->t('With search (RAG)')],
          'answer' => $this->buildAnswer($answer['answer'], $answer['results']),
        ];
        if ($can_debug && isset($answer['generation_debug'])) {
          $form['conversation']['outputs']['grounded']['prompt'] = $this->debugCode($this->t('Ollama prompt with retrieved sources'), $answer['generation_debug']);
        }
      }
      // Questions get an answer first. The three rankings for the same
      // question follow: Semantic's list is what the answer was written from.
      $grid = $form['ranking'];
      unset($form['ranking']);
      $form['ranking_heading'] = $this->evidenceHeading();
      $form['ranking_note'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#attributes' => ['class' => ['med-generate-hint']],
        '#value' => $this->t('The homepage does not show these lists for questions. The answer above was written from the @strategy list.', ['@strategy' => $retrieval]),
      ];
      $form['ranking'] = $grid;
    }
    return $form;
  }

  /**
   * Heading that presents the method cards as the evidence for the verdict.
   */
  private function evidenceHeading(): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#attributes' => ['class' => ['med-compare-subheading']],
      '#value' => $this->t('The evidence: how each method handles this search'),
    ];
  }

  /**
   * Formats request details as escaped JSON.
   */
  private function debugCode($label, array $data): array {
    return [
      '#type' => 'details',
      '#title' => $label,
      '#attributes' => ['class' => ['med-debug', 'med-solr-debug']],
      'code' => [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        'json' => [
          '#type' => 'html_tag',
          '#tag' => 'code',
          'value' => ['#plain_text' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR)],
        ],
      ],
    ];
  }

  /**
   * Explains why the homepage prefers this strategy for the query.
   */
  private function whyThisOne($text): array {
    $help = MetricHelp::build($this->t('Why this one?'), $text);
    $help['#attributes']['class'][] = 'med-why-this-one';
    return $help;
  }

  /**
   * Attributes for the card the homepage would use: ribbon and emphasis.
   */
  private function homepageCard(array $classes = []): array {
    return [
      'class' => array_merge($classes, ['med-homepage-card']),
      'aria-description' => (string) $this->t('The homepage uses this search for your query.'),
    ];
  }

  /**
   * Diagonal corner ribbon in its own clipping box, so popups are not cut.
   */
  private function cornerRibbon(): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => ['class' => ['med-corner-ribbon'], 'aria-hidden' => 'true'],
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $this->t('Homepage'),
      ],
    ];
  }

  /**
   * Marks the strategy's role in the homepage experience.
   */
  private function homepageRibbon($label): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => ['class' => ['med-homepage-ribbon']],
      '#value' => $label,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $query = trim((string) ($form_state->getTriggeringElement()['#med_example'] ?? $form_state->getValue('query')));
    if ($query === '' || mb_strlen($query) > 500) {
      $form_state->setErrorByName('query', $this->t('Enter a query between 1 and 500 characters.'));
    }
    $form_state->setValue('query', $query);
    $input = $form_state->getUserInput();
    $input['query'] = $query;
    $form_state->setUserInput($input);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $response = [];
    foreach (['lexical', 'semantic', 'hybrid'] as $id) {
      $start = hrtime(TRUE);
      try {
        $strategy = $this->strategies->get($id);
        $query = (string) $form_state->getValue('query');
        $can_debug = $this->currentUser()->hasPermission('view med search ranking details');
        $result = $id === 'lexical' ? $strategy->search($query, 5) : $strategy->search($query, 5, $can_debug);
        $result['comparison_ms'] = (hrtime(TRUE) - $start) / 1e6;
        $response[$id] = $result;
      }
      catch (\RuntimeException) {
        $response[$id] = NULL;
      }
    }
    $query = (string) $form_state->getValue('query');
    // Questions are answered automatically, as on the homepage.
    if ($this->router->route($query) === HomepageRouter::MED_ASK) {
      try {
        $answer = $this->strategies->get('conversational')->search($query, NULL, $this->currentUser()->hasPermission('view med search ranking details'));
      }
      catch (\RuntimeException) {
        $answer = ['answer' => ['status' => 'unavailable'], 'results' => []];
      }
      // The source-free comparison runs only for the fixed educational demo.
      if ($query === self::MED_BASELINE_QUESTION) {
        try {
          $baseline = $this->generator->generate(self::MED_BASELINE_INSTRUCTIONS, self::MED_BASELINE_QUESTION, [
            'type' => 'object',
            'properties' => ['answer' => ['type' => 'string']],
            'required' => ['answer'],
          ]);
          $text = trim(is_string($baseline['answer'] ?? NULL) ? $baseline['answer'] : '');
          if ($text !== '' && mb_strlen($text) <= 600 && !preg_match('/\b(?:you|your)\b/i', $text)) {
            $safe = TRUE;
            foreach (ConversationalSearch::MED_UNSAFE_PATTERNS as $pattern) {
              $safe = $safe && !preg_match($pattern, $text);
            }
            if ($safe) {
              $answer['baseline'] = $text;
            }
          }
        }
        catch (\RuntimeException) {
          // The cited answer remains available if the baseline fails.
        }
      }
      $form_state->setTemporaryValue('comparison_answer', $answer);
    }
    $form_state->setTemporaryValue('comparison', $response)->disableCache();
    $form_state->setRebuild();
  }

}
