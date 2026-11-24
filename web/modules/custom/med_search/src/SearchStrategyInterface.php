<?php

namespace Drupal\med_search;

/**
 * One retrieval strategy over the English Health Topic corpus.
 *
 * Every strategy returns the same structure so the form, enrichment and
 * evaluation code never depend on how a ranking was produced:
 *
 * @code
 * [
 *   'results' => [[
 *     'node' => NodeInterface,  // published, viewable English topic
 *     'id' => '157',            // canonical MedlinePlus topic ID
 *     'score' => 0.79,          // strategy-native; not comparable across strategies
 *     'ranks' => ['lexical' => 1, 'semantic' => 2],
 *   ], ...],
 *   'timings' => ['lexical_ms' => 3.1, ...],
 *   'notices' => ['Plain-text notice shown above the results.'],
 * ]
 * @endcode
 */
interface SearchStrategyInterface {

  /**
   * Machine name: semantic, lexical or hybrid.
   */
  public function id(): string;

  /**
   * Run a validated query, returning at most $limit deduplicated topics.
   *
   * @throws \InvalidArgumentException
   *   For an empty or over-long query or an invalid limit.
   * @throws \RuntimeException
   *   When the retrieval backend is unavailable.
   */
  public function search(string $query, ?int $limit = NULL): array;

  /**
   * Plain-text explanation of a result's score for the interface.
   */
  public function describeScore(array $result): string;

}
