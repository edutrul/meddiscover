<?php

namespace Drupal\med_search;

/**
 * Adapts the Phase 03 pgvector search to the shared strategy contract.
 */
class SemanticStrategy implements SearchStrategyInterface {

  public function __construct(private SemanticSearch $search) {}

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return 'semantic';
  }

  /**
   * {@inheritdoc}
   */
  public function search(string $query, ?int $limit = NULL): array {
    if ($limit !== NULL) {
      QueryText::validate($query, $limit);
    }
    $response = $this->search->search($query, NULL, $limit);
    $results = [];
    foreach ($response['results'] as $position => $row) {
      $results[] = [
        'node' => $row['node'],
        'id' => (string) $row['node']->field_med_source_id->value,
        'score' => $row['similarity'],
        'ranks' => ['semantic' => $position + 1],
      ];
    }
    return [
      'results' => $results,
      'timings' => ['embedding_ms' => $response['embedding_ms'], 'semantic_ms' => $response['retrieval_ms']],
      'notices' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function describeScore(array $result): string {
    return 'Cosine similarity: ' . number_format($result['score'], 4);
  }

}
