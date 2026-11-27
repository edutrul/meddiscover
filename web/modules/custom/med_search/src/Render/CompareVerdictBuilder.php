<?php

namespace Drupal\med_search\Render;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\med_search\GeneratorInterface;
use Drupal\med_search\HomepageRouter;
use Drupal\med_search\StrategyBrand;

/**
 * The Compare verdict: what the homepage would answer, why, and how.
 *
 * Decides the wording and gathers live values; the markup lives in the
 * med_compare_verdict, med_compare_flow and med_compare_tech templates, so
 * themes can override it.
 */
final class CompareVerdictBuilder {

  use StringTranslationTrait;

  public function __construct(
    private HomepageRouter $router,
    private GeneratorInterface $generator,
    private ConfigFactoryInterface $configFactory,
    private StrategyBrand $brand,
    private AccountInterface $currentUser,
  ) {}

  /**
   * Builds the verdict for one Compare search.
   *
   * @param string $query
   *   The search text.
   * @param array $response
   *   The three rankings, keyed lexical, semantic and hybrid.
   * @param array|null $answer
   *   The conversational response for questions, or NULL.
   * @param string|\Stringable $retrieval
   *   Label of the retrieval used for answers.
   */
  public function build(string $query, array $response, ?array $answer, string|\Stringable $retrieval): array {
    $route = $this->router->route($query);
    $urgent = $this->router->urgency($query);
    $hybrid_results = $response['hybrid']['results'] ?? [];
    $nothing_found = $route === HomepageRouter::MED_HYBRID && !$hybrid_results;
    [$label, $get, $why] = $this->wording($query, $route, $nothing_found, $retrieval);

    $build = [
      '#theme' => 'med_compare_verdict',
      '#path' => $route,
      '#label' => $label,
      '#get' => $get,
      '#why' => $why,
      '#topics' => $route === HomepageRouter::MED_HYBRID
        ? array_map(static fn(array $item): array => $item['node']->toLink()->toRenderable(), $hybrid_results)
        : [],
      '#flow' => [
        '#theme' => 'med_compare_flow',
        '#query' => $query,
        '#path' => $route,
        '#images' => $this->brand->imagePath(),
      ],
      '#technical' => [],
    ];
    if ($this->currentUser->hasPermission('view med search ranking details')) {
      $build['#technical'] = [
        '#type' => 'details',
        '#title' => $this->t('How the homepage computes this (technical)'),
        '#open' => TRUE,
        '#attributes' => ['class' => ['med-debug', 'med-verdict-tech']],
        'body' => [
          '#theme' => 'med_compare_tech',
          '#path' => $route,
          '#data' => $this->technicalData($query, $urgent, $response, $answer),
        ],
      ];
    }
    return $build;
  }

  /**
   * Label, "you'd get" and "why" for the homepage route.
   */
  private function wording(string $query, string $route, bool $nothing_found, string|\Stringable $retrieval): array {
    $quoted = '“' . $query . '”';
    $topic_rule = $this->t('@query has no “?” and does not start with a question word, so it counts as a topic search.', ['@query' => $quoted]);
    return match (TRUE) {
      $route === HomepageRouter::MED_URGENT => [
        $this->t('Urgent-safety message'),
        $this->t('Emergency guidance only: call 911 or your local emergency number. No topic list and no AI answer.'),
        $this->t('@query contains urgent wording, which is checked before anything else.', ['@query' => $quoted]),
      ],
      $route === HomepageRouter::MED_ASK => [
        $this->t('Ask (RAG)'),
        $this->t('A short answer with citations, written from the top topics of @strategy search. It is shown below.', ['@strategy' => $retrieval]),
        $this->t('@query @reason, so it counts as a question.', [
          '@query' => $quoted,
          '@reason' => $this->router->questionReason($query),
        ]),
      ],
      $nothing_found => [
        $this->t('No result'),
        $this->t('“No topics matched closely enough.” None of the three methods found a topic. Check the spelling or try other words.'),
        $this->t('@rule The homepage would run Hybrid, but Hybrid found nothing.', ['@rule' => $topic_rule]),
      ],
      default => [
        $this->t('Hybrid (Keyword + Semantic)'),
        $this->t('A ranked list of health topics:'),
        $topic_rule,
      ],
    };
  }

  /**
   * Live values for the Developer mode breakdown (med_compare_tech).
   *
   * Every stage of the chosen path: routing, Solr, pgvector and RRF, or
   * retrieval, prompt and verification for RAG, with this search's own
   * scores, ranks, settings and timings.
   */
  private function technicalData(string $query, ?string $urgent, array $response, ?array $answer): array {
    $config = $this->configFactory->get('med_search.settings');
    $ms = static fn($value): string => $value === NULL ? '–' : number_format((float) $value, 1) . ' ms';
    $lexical = $response['lexical'] ?? NULL;
    $semantic = $response['semantic'] ?? NULL;
    $hybrid = $response['hybrid'] ?? NULL;
    $solr = $lexical['solr_debug']['parameters'] ?? [];
    $embedding = $semantic['embedding_debug'] ?? [];
    $rows = static fn(?array $list): array => $list ?? [];
    return [
      'query' => $query,
      'pattern' => HomepageRouter::MED_QUESTION_PATTERN,
      'reason' => $this->router->questionReason($query),
      'urgent' => $urgent,
      'solr_q' => $solr['q'] ?? '',
      'solr_mm' => $mm = $solr['mm'] ?? (string) $config->get('lexical_min_match'),
      // "N<75%": up to N terms all are required; above N, 75% of them.
      'solr_mm_all' => preg_match('/^(\d+)<75%$/', $mm, $m) ? (int) $m[1] : NULL,
      'solr_pf' => $solr['pf'] ?? '',
      'lexical' => array_map(static fn(array $row, int $i): array => [
        'rank' => $i + 1,
        'title' => $row['node']->label(),
        'score' => number_format((float) $row['score'], 2),
      ], $rows($lexical['results'] ?? NULL), array_keys($rows($lexical['results'] ?? NULL))),
      'lexical_ms' => $ms($lexical['timings']['lexical_ms'] ?? NULL),
      'embedding_model' => $embedding['model'] ?? '',
      'embedding_input' => $embedding['input'] ?? 'search_query: ' . $query,
      'dimensions' => $embedding['response']['dimensions'] ?? (int) $config->get('dimensions'),
      'vector_head' => isset($embedding['response']['embedding'])
        ? implode(', ', array_map(static fn($v): string => number_format((float) $v, 4), array_slice($embedding['response']['embedding'], 0, 4)))
        : NULL,
      'threshold' => (string) $config->get('threshold'),
      'semantic' => array_map(static fn(array $row, int $i): array => [
        'rank' => $i + 1,
        'title' => $row['node']->label(),
        'cosine' => number_format((float) $row['score'], 4),
        'distance' => number_format(1 - (float) $row['score'], 4),
      ], $rows($semantic['results'] ?? NULL), array_keys($rows($semantic['results'] ?? NULL))),
      'embedding_ms' => $ms($semantic['timings']['embedding_ms'] ?? NULL),
      'semantic_ms' => $ms($semantic['timings']['semantic_ms'] ?? NULL),
      'k' => (int) $config->get('hybrid_rrf_k'),
      'depth' => (int) $config->get('hybrid_depth'),
      'rrf' => array_map(static fn(array $row, int $i): array => [
        'rank' => $i + 1,
        'title' => $row['title'],
        'lexical_rank' => $row['ranks']['lexical'] ?? '–',
        'semantic_rank' => $row['ranks']['semantic'] ?? '–',
        'lexical_points' => isset($row['points_by_list']['lexical']) ? number_format($row['points_by_list']['lexical'], 5) : '0',
        'semantic_points' => isset($row['points_by_list']['semantic']) ? number_format($row['points_by_list']['semantic'], 5) : '0',
        'total' => number_format($row['rrf_score'], 5),
      ], $rows($hybrid['hybrid_debug']['results'] ?? NULL), array_keys($rows($hybrid['hybrid_debug']['results'] ?? NULL))),
      'fusion_ms' => $ms($hybrid['timings']['fusion_ms'] ?? NULL),
      'rag_sources' => (int) $config->get('rag_sources'),
      'rag_chars' => (int) $config->get('rag_source_chars'),
      'generation_model' => $this->generator->modelId(),
      'generation_max' => (int) $config->get('generation_max_tokens'),
      'answer_status' => isset($answer['answer']['status'])
        ? $answer['answer']['status'] . (isset($answer['answer']['reason']) ? ' (' . $answer['answer']['reason'] . ')' : '')
        : NULL,
      'answer_statements' => count($answer['answer']['statements'] ?? []),
      'answer_dropped' => $answer['answer']['dropped'] ?? 0,
      // Source labels follow retrieval order (the answer lists cited topics
      // first, which is not the order the model saw).
      'answer_sources' => $answer === NULL ? [] : array_map(static fn(array $row, int $i): array => [
        'label' => 'S' . ($i + 1),
        'title' => $row['node']->label(),
      ], $sources = array_slice($rows($semantic['results'] ?? NULL), 0, (int) $config->get('rag_sources')), array_keys($sources)),
      'answer_embedding_ms' => $ms($answer['timings']['embedding_ms'] ?? NULL),
      'answer_retrieval_ms' => $ms($answer['timings']['semantic_ms'] ?? NULL),
      'generation_ms' => $ms($answer['timings']['generation_ms'] ?? NULL),
    ];
  }

}
