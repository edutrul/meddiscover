<?php

namespace Drupal\med_search;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Reciprocal Rank Fusion of lexical (Solr) and semantic (pgvector) rankings.
 *
 * Only ranks are fused. Solr scores and cosine similarities have different,
 * query-dependent scales, so they are never added or normalized together.
 */
final class HybridSearch implements SearchStrategyInterface {

  use StringTranslationTrait;

  /**
   * Constructs the hybrid strategy.
   *
   * @param \Drupal\med_search\SearchStrategyInterface[] $strategies
   *   Keyed by strategy ID, in tie-break priority order.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Reads hybrid_depth and hybrid_rrf_k.
   */
  public function __construct(
    private array $strategies,
    private ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Service factory: lexical first, then semantic.
   */
  public static function create(SearchStrategyInterface $lexical, SearchStrategyInterface $semantic, ConfigFactoryInterface $config_factory): static {
    return new static(['lexical' => $lexical, 'semantic' => $semantic], $config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return 'hybrid';
  }

  /**
   * {@inheritdoc}
   */
  public function search(string $query, ?int $limit = NULL, bool $includeRanking = FALSE): array {
    $config = $this->configFactory->get('med_search.settings');
    $limit ??= (int) $config->get('result_limit');
    QueryText::validate($query, $limit);
    $depth = (int) $config->get('hybrid_depth');
    $k = (int) $config->get('hybrid_rrf_k');

    $rankings = $nodes = $timings = $notices = $failed = [];
    foreach ($this->strategies as $id => $strategy) {
      try {
        // Each retriever applies its own acceptance rule (cosine threshold or
        // Solr minimum-should-match), so unrelated queries can stay empty.
        $response = $strategy->search($query, $depth);
      }
      catch (\InvalidArgumentException $e) {
        throw $e;
      }
      catch (\Throwable) {
        $failed[] = $id;
        continue;
      }
      $rankings[$id] = array_column($response['results'], 'id');
      foreach ($response['results'] as $result) {
        $nodes[$result['id']] ??= $result['node'];
      }
      $timings += $response['timings'];
      $notices = array_merge($notices, $response['notices']);
    }
    if (!$rankings) {
      throw new \RuntimeException('No retrieval strategy is available.');
    }
    foreach ($failed as $id) {
      $notices[] = $this->t('@failed retrieval is unavailable, so these results use @used ranking only.', [
        '@failed' => ucfirst($id),
        '@used' => implode(' + ', array_keys($rankings)),
      ]);
    }

    $start = hrtime(TRUE);
    $results = [];
    foreach (array_slice(self::fuse($rankings, $k), 0, $limit) as $row) {
      $results[] = ['node' => $nodes[$row['id']]] + $row;
    }
    $timings['fusion_ms'] = (hrtime(TRUE) - $start) / 1e6;
    $response = ['results' => $results, 'timings' => $timings, 'notices' => array_values(array_unique($notices))];
    if ($includeRanking) {
      $response['hybrid_debug'] = [
        'method' => 'Reciprocal Rank Fusion',
        'k' => $k,
        'candidate_limit_per_list' => $depth,
        'input_rankings' => array_map(static fn(array $ids): array => array_map(static fn($id): string => $nodes[$id]->label(), $ids), $rankings),
        'results' => array_map(static fn(array $row): array => [
          'title' => $row['node']->label(),
          'ranks' => $row['ranks'],
          'points_by_list' => array_map(static fn(int $rank): float => 1 / ($k + $rank), $row['ranks']),
          'rrf_score' => $row['score'],
        ], $results),
      ];
    }
    return $response;
  }

  /**
   * Fuse rankings: RRF(d) = sum over lists of 1 / (k + rank(d)).
   *
   * @param array $rankings
   *   Lists of canonical topic IDs keyed by strategy, best first. Ranks start
   *   at 1; a repeated ID inside one list keeps its first rank.
   * @param int $k
   *   Smoothing constant (>= 0). Larger values flatten rank differences.
   *
   * @return array
   *   Rows with id, score and ranks, ordered by score, then by appearing in
   *   more lists, then by best single rank, then by the earliest strategy in
   *   $rankings holding that best rank, then by ID.
   */
  public static function fuse(array $rankings, int $k): array {
    if ($k < 0) {
      throw new \InvalidArgumentException('RRF k must be zero or positive.');
    }
    $rows = [];
    $priority = array_flip(array_keys($rankings));
    foreach ($rankings as $strategy => $ids) {
      $rank = 0;
      $seen = [];
      foreach ($ids as $id) {
        $id = (string) $id;
        if (isset($seen[$id])) {
          continue;
        }
        $seen[$id] = TRUE;
        $rank++;
        $rows[$id] ??= ['id' => $id, 'score' => 0.0, 'ranks' => []];
        $rows[$id]['score'] += 1 / ($k + $rank);
        $rows[$id]['ranks'][$strategy] = $rank;
      }
    }
    $key = function (array $row) use ($priority): array {
      $best = min($row['ranks']);
      $first = min(array_map(fn($s) => $priority[$s], array_keys($row['ranks'], $best, TRUE)));
      return [-$row['score'], -count($row['ranks']), $best, $first, $row['id']];
    };
    uasort($rows, fn($a, $b) => $key($a) <=> $key($b));
    return array_values($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function describeScore(array $result): TranslatableMarkup {
    return $this->t('RRF score: @score (Lexical rank @lexical, Semantic rank @semantic)', [
      '@score' => number_format($result['score'], 4),
      '@lexical' => $result['ranks']['lexical'] ?? '–',
      '@semantic' => $result['ranks']['semantic'] ?? '–',
    ]);
  }

}
