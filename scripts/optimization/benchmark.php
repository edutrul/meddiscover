<?php

/**
 * @file
 * Stage latency benchmark on the real stack with synthetic queries.
 *
 * Usage: ddev drush php:script scripts/optimization/benchmark.php [llm=10]
 * Measures embedding, retrieval, enrichment, activation, LLM generation and
 * end-to-end time per strategy, plus error counts. Prints Markdown.
 */

$args = [];
foreach ($extra as $arg) {
  [$key, $value] = explode('=', $arg, 2) + [1 => ''];
  $args[$key] = $value;
}
$root = dirname(DRUPAL_ROOT);
$queries = array_column(json_decode(file_get_contents($root . '/docs/evaluation/phase-05-queries.json'), TRUE)['cases'], 'query');
$answerable = array_column(array_filter(json_decode(file_get_contents($root . '/docs/evaluation/phase-06-queries.json'), TRUE)['cases'], fn($c) => $c['category'] === 'answerable'), 'query');
$answerable = array_slice($answerable, 0, (int) ($args['llm'] ?? 10));
$registry = \Drupal::service('med_search.strategies');
$enricher = \Drupal::service('med_search.enricher');
$activation = \Drupal::hasService('med_activation.engine') ? \Drupal::service('med_activation.engine') : NULL;

/**
 * Median, p90 and max of a list.
 */
function bench_stats(array $values): array {
  sort($values);
  $n = count($values);
  if (!$n) {
    return [NULL, NULL, NULL];
  }
  return [$values[intdiv($n, 2)], $values[max(0, (int) ceil(0.9 * $n) - 1)], end($values)];
}

$plans = ['lexical' => $queries, 'semantic' => $queries, 'hybrid' => $queries, 'conversational' => $answerable];
$rows = [];
foreach ($plans as $id => $list) {
  $strategy = $registry->get($id);
  // Warm-up outside the measurements.
  $strategy->search('Synthetic warm-up question about knees');
  $stages = [];
  $errors = 0;
  foreach ($list as $query) {
    $start = hrtime(TRUE);
    try {
      $response = $strategy->search($query);
    }
    catch (\Throwable) {
      $errors++;
      continue;
    }
    $search_ms = (hrtime(TRUE) - $start) / 1e6;
    $t = $response['timings'];
    $enrich = $activate = 0.0;
    foreach ($response['results'] as $result) {
      $s = hrtime(TRUE);
      $data = $enricher->enrich($result['node']);
      $enrich += (hrtime(TRUE) - $s) / 1e6;
      if ($activation) {
        $s = hrtime(TRUE);
        $activation->recommend($result['node'], $query, $data);
        $activate += (hrtime(TRUE) - $s) / 1e6;
      }
    }
    foreach ([
      'embedding' => $t['embedding_ms'] ?? NULL,
      'retrieval' => ($t['semantic_ms'] ?? 0) + ($t['lexical_ms'] ?? 0) + ($t['fusion_ms'] ?? 0) ?: NULL,
      'generation' => $t['generation_ms'] ?? NULL,
      'enrichment' => $response['results'] ? $enrich : NULL,
      'activation' => $response['results'] && $activation ? $activate : NULL,
      'end-to-end' => $search_ms + $enrich + $activate,
    ] as $stage => $value) {
      if ($value !== NULL) {
        $stages[$stage][] = $value;
      }
    }
  }
  foreach ($stages as $stage => $values) {
    [$median, $p90, $max] = bench_stats($values);
    $rows[] = sprintf('| %s | %s | %d | %.1f | %.1f | %.1f |', $id, $stage, count($values), $median, $p90, $max);
  }
  $rows[] = sprintf('| %s | errors | %d | %d of %d queries | | |', $id, $errors, $errors, count($list));
}
printf("Real corpus (24 topics), local DDEV on the host machine, %d retrieval queries (Phase 05 set) and %d conversational queries (Phase 06 answerable set); one warm-up per strategy. Milliseconds.\n\n", count($queries), count($answerable));
echo "| Strategy | Stage | Samples | Median | p90 | Max |\n|---|---|---|---|---|---|\n" . implode("\n", $rows) . "\n";
