<?php

/**
 * @file
 * Real-corpus activation report: coverage, URL fidelity and context examples.
 *
 * Usage: ddev drush php:script scripts/activation/report.php
 * Uses the configured rules and synthetic example queries only.
 */

$engine = \Drupal::service('med_activation.engine');
$enricher = \Drupal::service('med_search.enricher');
$storage = \Drupal::entityTypeManager()->getStorage('node');
$ids = $storage->getQuery()->accessCheck(FALSE)->condition('type', 'med_health_topic')->condition('status', 1)->sort('title')->execute();
$per_rule = $hosts = $counts = [];
$checked = $violations = 0;
foreach ($storage->loadMultiple($ids) as $topic) {
  $data = $enricher->enrich($topic);
  $allowed = array_merge(array_column($data['related'], 'url'), ...array_map(fn($items) => array_column($items, 'url'), array_values($data['resources'])));
  $items = $engine->recommend($topic, '', $data);
  $counts[] = count($items);
  foreach ($items as $item) {
    $checked++;
    $per_rule[$item['label'] === 'Explore: ' . $item['title'] ? 'related topic' : $item['label']] = ($per_rule[$item['label'] === 'Explore: ' . $item['title'] ? 'related topic' : $item['label']] ?? 0) + 1;
    if (!in_array($item['url'], $allowed, TRUE)) {
      $violations++;
      echo "VIOLATION: {$topic->label()} {$item['label']} {$item['url']}\n";
    }
    $hosts[parse_url($item['url'], PHP_URL_HOST)] = TRUE;
  }
}
sort($counts);
printf("Topics: %d. CTAs checked: %d. URLs not found in the topic's imported resources or relationships: %d. Distinct destination hosts: %d. CTAs per topic: min %d, median %d, max %d.\n\n", count($ids), $checked, $violations, count($hosts), $counts[0], $counts[intdiv(count($counts), 2)], end($counts));
echo "| Call to action (default page, no search context) | Topics |\n|---|---|\n";
arsort($per_rule);
foreach ($per_rule as $label => $n) {
  echo "| $label | $n |\n";
}
echo "\n| Synthetic search | Top result | Next steps (in order) |\n|---|---|---|\n";
$semantic = \Drupal::service('med_search.strategies')->get('semantic');
foreach (['What is back pain?', 'Are there clinical trials for migraine?', 'Find a doctor for knee pain', 'Latest research on type 2 diabetes', 'Printable guide for hay fever', 'Back pain'] as $query) {
  $top = $semantic->search($query, 1)['results'][0]['node'] ?? NULL;
  $labels = $top ? array_map(fn($i) => $i['label'] . ($i['keyword'] ? ' (“' . $i['keyword'] . '”)' : ''), $engine->recommend($top, $query)) : [];
  printf("| %s | %s | %s |\n", $query, $top ? $top->label() : '—', implode(' → ', $labels));
}
if ($violations) {
  throw new RuntimeException("$violations CTA URLs are not imported source URLs.");
}
