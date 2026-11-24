<?php

/**
 * @file
 * Evaluate only reviewed synthetic queries; never capture live user queries.
 */

$root = dirname(DRUPAL_ROOT);
$cases = json_decode(file_get_contents($root . '/docs/evaluation/semantic-queries.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$service = \Drupal::service('med_search.search');
$settings = \Drupal::config('med_search.settings')->getRawData();
$rows = [];
foreach ($cases['cases'] as $case) {
  $response = $service->search($case['query'], -1.0, 20);
  $ranking = [];
  foreach ($response['results'] as $position => $result) {
    $ranking[] = ['rank' => $position + 1, 'source_id' => $result['node']->field_med_source_id->value,
      'title' => $result['node']->label(), 'similarity' => $result['similarity']];
  }
  $rows[] = $case + ['ranking' => $ranking, 'returned_ids' => array_column(array_slice(array_filter($ranking, fn($r) => $r['similarity'] >= $settings['threshold']), 0, $settings['result_limit']), 'source_id'),
    'embedding_ms' => $response['embedding_ms'], 'retrieval_ms' => $response['retrieval_ms']];
}
$output = ['model' => $settings['model'], 'model_digest' => $settings['model_digest'],
  'dimensions' => $settings['dimensions'], 'threshold' => $settings['threshold'],
  'include_metadata' => $settings['include_metadata'], 'cases' => $rows];
echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
