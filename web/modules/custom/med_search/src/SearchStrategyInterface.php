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
   * Machine name: semantic, lexical, hybrid or conversational.
   */
  public function id(): string;

  /**
   * Human-readable name, e.g. "Hybrid (Keyword + Semantic)".
   */
  public function label(): string|\Stringable;

  /**
   * One-line explanation of how this strategy finds topics.
   */
  public function description(): string|\Stringable;

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
   * Translatable plain-text explanation of a result's score.
   */
  public function describeScore(array $result): string|\Stringable;

}
