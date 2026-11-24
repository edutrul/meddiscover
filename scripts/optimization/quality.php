<?php

/**
 * @file
 * Search quality metrics for a reviewed query set, with regression checks.
 *
 * Usage:
 *   ddev drush php:script scripts/optimization/quality.php \
 *     queries=docs/evaluation/phase-08-holdout-queries.json \
 *     [out=docs/evaluation/baselines/holdout.json] \
 *     [baseline=docs/evaluation/baselines/holdout.json]
 *
 * Needs the real corpus, Solr and the local embedding model. Synthetic
 * queries only. With baseline=..., exits with an error when a strategy's
 * MRR@5 or recall drops by more than 0.02, its unrelated-query empty rate
 * drops, or a positive query loses its first relevant result.
 */

$args = [];
foreach ($extra as $arg) {
  [$key, $value] = explode('=', $arg, 2) + [1 => ''];
  $args[$key] = $value;
}
$root = dirname(DRUPAL_ROOT);
$set = json_decode(file_get_contents($root . '/' . ($args['queries'] ?? 'docs/evaluation/phase-08-holdout-queries.json')), TRUE, 512, JSON_THROW_ON_ERROR);
$cut = (int) ($set['k'] ?? 5);
$registry = \Drupal::service('med_search.strategies');

$summary = ['queries' => $args['queries'] ?? 'docs/evaluation/phase-08-holdout-queries.json', 'k' => $cut, 'strategies' => []];
foreach (['lexical', 'semantic', 'hybrid'] as $id) {
  $strategy = $registry->get($id);
  $rows = $per_query = [];
  foreach ($set['cases'] as $case) {
    $ranking = array_slice(array_column($strategy->search($case['query'], $cut)['results'], 'id'), 0, $cut);
    $relevant = $case['relevant_ids'];
    $first = NULL;
    foreach ($ranking as $position => $topic) {
      if (in_array($topic, $relevant, TRUE)) {
        $first = $position + 1;
        break;
      }
    }
    $rows[] = [
      'category' => $case['category'],
      'positive' => $relevant !== [],
      'empty' => $ranking === [],
      'rr' => $first ? 1 / $first : 0.0,
      'p1' => $first === 1 ? 1.0 : 0.0,
      'recall' => $relevant ? count(array_intersect($ranking, $relevant)) / count($relevant) : NULL,
      'precision' => count(array_intersect($ranking, $relevant)) / $cut,
    ];
    $per_query[$case['id']] = ['first_relevant_rank' => $first, 'top' => $ranking];
  }
  $positive = array_filter($rows, fn($r) => $r['positive']);
  $unrelated = array_filter($rows, fn($r) => !$r['positive']);
  $mean = fn(array $list, string $key) => $list ? round(array_sum(array_column($list, $key)) / count($list), 3) : NULL;
  $categories = [];
  foreach (array_unique(array_column($positive, 'category')) as $category) {
    $categories[$category] = $mean(array_filter($positive, fn($r) => $r['category'] === $category), 'rr');
  }
  $summary['strategies'][$id] = [
    'positive_queries' => count($positive),
    'p_at_1' => $mean($positive, 'p1'),
    'p_at_k' => $mean($positive, 'precision'),
    'recall_at_k' => $mean($positive, 'recall'),
    'mrr_at_k' => $mean($positive, 'rr'),
    'zero_result_rate' => $positive ? round(count(array_filter($positive, fn($r) => $r['empty'])) / count($positive), 3) : NULL,
    'unrelated_queries' => count($unrelated),
    'unrelated_empty_rate' => $unrelated ? round(count(array_filter($unrelated, fn($r) => $r['empty'])) / count($unrelated), 3) : NULL,
    'mrr_by_category' => $categories,
    'per_query' => $per_query,
  ];
}

$failures = [];
if (!empty($args['baseline'])) {
  $baseline = json_decode(file_get_contents($root . '/' . $args['baseline']), TRUE, 512, JSON_THROW_ON_ERROR);
  foreach ($summary['strategies'] as $id => $now) {
    $before = $baseline['strategies'][$id];
    foreach (['mrr_at_k', 'recall_at_k'] as $metric) {
      if ($now[$metric] < $before[$metric] - 0.02) {
        $failures[] = "$id $metric {$before[$metric]} → {$now[$metric]}";
      }
    }
    if ($now['unrelated_empty_rate'] < $before['unrelated_empty_rate']) {
      $failures[] = "$id unrelated_empty_rate {$before['unrelated_empty_rate']} → {$now['unrelated_empty_rate']}";
    }
    foreach ($now['per_query'] as $query => $result) {
      $old = $before['per_query'][$query]['first_relevant_rank'] ?? NULL;
      if ($old !== NULL && ($result['first_relevant_rank'] === NULL || $result['first_relevant_rank'] > $old)) {
        $failures[] = "$id ranking regression $query: rank $old → " . ($result['first_relevant_rank'] ?? 'missing');
      }
    }
  }
}

echo "| Strategy | P@1 | P@$cut | R@$cut | MRR@$cut | Zero-result rate (positive) | Unrelated returning nothing |\n|---|---|---|---|---|---|---|\n";
foreach ($summary['strategies'] as $id => $s) {
  printf("| %s | %.3f | %.3f | %.3f | %.3f | %.3f | %d/%d |\n", $id, $s['p_at_1'], $s['p_at_k'], $s['recall_at_k'], $s['mrr_at_k'], $s['zero_result_rate'], round($s['unrelated_empty_rate'] * $s['unrelated_queries']), $s['unrelated_queries']);
}
if (!empty($args['out'])) {
  file_put_contents($root . '/' . $args['out'], json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
  echo "\nWrote {$args['out']}\n";
}
if ($failures) {
  echo "\nRegressions against {$args['baseline']}:\n- " . implode("\n- ", $failures) . "\n";
  throw new RuntimeException(count($failures) . ' quality regressions.');
}
if (!empty($args['baseline'])) {
  echo "\nNo regressions against {$args['baseline']}.\n";
}
