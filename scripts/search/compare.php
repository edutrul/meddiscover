<?php

/**
 * @file
 * Compare lexical, semantic and hybrid retrieval on reviewed synthetic queries.
 *
 * Usage: ddev drush php:script scripts/search/compare.php
 * Needs the real corpus, the Solr index and the local embedding model. Prints
 * Markdown tables; never reads or records live user queries.
 */

use Drupal\med_search\HybridSearch;

$root = dirname(DRUPAL_ROOT);
$set = json_decode(file_get_contents($root . '/docs/evaluation/phase-05-queries.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$k_cut = (int) $set['k'];
$settings = \Drupal::config('med_search.settings');
$depth = (int) $settings->get('hybrid_depth');
$rrf_k = (int) $settings->get('hybrid_rrf_k');
$strategies = [
  'lexical' => \Drupal::service('med_search.strategy.lexical'),
  'semantic' => \Drupal::service('med_search.strategy.semantic'),
  'hybrid' => \Drupal::service('med_search.strategy.hybrid'),
];
$repeats = 3;

/**
 * Median of a list of numbers.
 */
function compare_median(array $values): float {
  sort($values);
  $n = count($values);
  return $n ? ($n % 2 ? $values[intdiv($n, 2)] : ($values[$n / 2 - 1] + $values[$n / 2]) / 2) : 0.0;
}

/**
 * Nearest-rank percentile.
 */
function compare_percentile(array $values, float $p): float {
  sort($values);
  return $values ? $values[max(0, (int) ceil($p * count($values)) - 1)] : 0.0;
}

/**
 * Precision, recall and reciprocal rank at the cut-off for one ranking.
 */
function compare_metrics(array $ranking, array $relevant, int $cut): array {
  $top = array_slice($ranking, 0, $cut);
  $hits = count(array_intersect($top, $relevant));
  $rr = 0.0;
  foreach ($top as $position => $id) {
    if (in_array($id, $relevant, TRUE)) {
      $rr = 1 / ($position + 1);
      break;
    }
  }
  return [
    'p1' => $top && in_array($top[0], $relevant, TRUE) ? 1.0 : 0.0,
    'p' => $hits / $cut,
    'r' => $relevant ? $hits / count($relevant) : NULL,
    'rr' => $rr,
    'empty' => $top === [],
    'count' => count($top),
  ];
}

// Warm up each strategy (model load, Solr caches) outside the measurements.
foreach ($strategies as $strategy) {
  $strategy->search('Synthetic warm-up query about health', $k_cut);
}

$rows = [];
foreach ($set['cases'] as $case) {
  $row = $case + ['rankings' => [], 'latency' => []];
  foreach ($strategies as $id => $strategy) {
    $times = [];
    for ($i = 0; $i < $repeats; $i++) {
      $start = hrtime(TRUE);
      $response = $strategy->search($case['query'], $id === 'hybrid' ? $k_cut : $depth);
      $times[] = (hrtime(TRUE) - $start) / 1e6;
    }
    $row['rankings'][$id] = array_column($response['results'], 'id');
    $row['latency'][$id] = compare_median($times);
  }
  // Offline fusion of the same depth-limited lists must reproduce the online
  // hybrid ranking at the configured k.
  $offline = array_column(HybridSearch::fuse(['lexical' => $row['rankings']['lexical'], 'semantic' => $row['rankings']['semantic']], $rrf_k), 'id');
  if (array_slice($offline, 0, $k_cut) !== $row['rankings']['hybrid']) {
    throw new RuntimeException('Offline fusion differs from the hybrid service for ' . $case['id']);
  }
  $rows[] = $row;
}

$categories = array_values(array_unique(array_column($set['cases'], 'category')));
$variants = ['lexical' => NULL, 'semantic' => NULL, 'hybrid' => $rrf_k];
foreach ([1, 10, 100] as $alt) {
  $variants["hybrid k=$alt"] = $alt;
}

/**
 * Ranking of one variant for one evaluated row.
 */
function compare_ranking(array $row, string $variant, ?int $k): array {
  if ($k === NULL || $variant === 'hybrid') {
    return $row['rankings'][$variant];
  }
  return array_column(HybridSearch::fuse(['lexical' => $row['rankings']['lexical'], 'semantic' => $row['rankings']['semantic']], $k), 'id');
}

printf("Corpus: %d published English topics. Cut-off K = %d. Hybrid: RRF k = %d over the top %d accepted results of each strategy. Latency: median of %d runs per query.\n\n", count(\Drupal::entityTypeManager()->getStorage('node')->getQuery()->accessCheck(FALSE)->condition('type', 'med_health_topic')->condition('status', 1)->execute()), $k_cut, $rrf_k, $depth, $repeats);

echo "| Strategy | Category | Queries | P@1 | P@$k_cut | R@$k_cut | MRR | Zero-result rate |\n|---|---|---|---|---|---|---|---|\n";
foreach ($variants as $variant => $k) {
  foreach (array_merge($categories, ['all relevant']) as $category) {
    $metrics = [];
    foreach ($rows as $row) {
      $positive = $row['relevant_ids'] !== [];
      if (($category === 'all relevant' && $positive) || $row['category'] === $category) {
        $metrics[] = compare_metrics(compare_ranking($row, $variant, $k), $row['relevant_ids'], $k_cut);
      }
    }
    if (!$metrics || (str_starts_with($variant, 'hybrid k=') && $category !== 'all relevant')) {
      continue;
    }
    $avg = fn($key) => array_sum(array_column($metrics, $key)) / count($metrics);
    $zero = count(array_filter(array_column($metrics, 'empty'))) / count($metrics);
    if ($category === 'unrelated') {
      printf("| %s | %s | %d | – | – | – | – | %.2f (mean %.1f results) |\n", $variant, $category, count($metrics), $zero, $avg('count'));
      continue;
    }
    printf("| %s | %s | %d | %.2f | %.2f | %.2f | %.2f | %.2f |\n", $variant, $category, count($metrics), $avg('p1'), $avg('p'), $avg('r'), $avg('rr'), $zero);
  }
}

echo "\n| RRF k | Queries whose top $k_cut differs from k = $rrf_k | Queries whose first result differs |\n|---|---|---|\n";
foreach ([0, 1, 10, 30, 100, 1000] as $alt) {
  $changed = $first = 0;
  foreach ($rows as $row) {
    $a = array_slice(compare_ranking($row, 'k', $alt), 0, $k_cut);
    $changed += $a !== $row['rankings']['hybrid'] ? 1 : 0;
    $first += ($a[0] ?? NULL) !== ($row['rankings']['hybrid'][0] ?? NULL) ? 1 : 0;
  }
  printf("| %d | %d of %d | %d |\n", $alt, $changed, count($rows), $first);
}

echo "\n| Strategy | Median ms | p90 ms | Max ms |\n|---|---|---|---|\n";
foreach (array_keys($strategies) as $id) {
  $values = array_column(array_column($rows, 'latency'), $id);
  printf("| %s | %.1f | %.1f | %.1f |\n", $id, compare_median($values), compare_percentile($values, 0.9), max($values));
}

echo "\n| Query ID | Category | Relevant | Lexical | Semantic | Hybrid |\n|---|---|---|---|---|---|\n";
foreach ($rows as $row) {
  $show = fn(array $ids) => $ids ? implode(', ', array_map(fn($id) => in_array($id, $row['relevant_ids'], TRUE) ? "**$id**" : $id, array_slice($ids, 0, $k_cut))) : '∅';
  printf("| %s | %s | %s | %s | %s | %s |\n", $row['id'], $row['category'], $row['relevant_ids'] ? implode(', ', $row['relevant_ids']) : '∅', $show($row['rankings']['lexical']), $show($row['rankings']['semantic']), $show($row['rankings']['hybrid']));
}
