<?php

/**
 * @file
 * Check storage for a specified synthetic form-test query, not a real query.
 */

$query = 'What is backache?';
$database = \Drupal::database();
foreach (['cache_form' => 'data', 'watchdog' => 'variables'] as $table => $column) {
  // Drupal creates cache bins lazily; a missing table stores nothing.
  if (!$database->schema()->tableExists($table)) {
    echo "PASS: $table does not exist, so the synthetic query is absent\n";
    continue;
  }
  $sql = "SELECT COUNT(*) FROM {" . $table . "} WHERE position(convert_to(:query, 'UTF8') in " . $column . ") > 0";
  $count = (int) $database->query($sql, [':query' => $query])->fetchField();
  if ($count !== 0) {
    throw new RuntimeException('Synthetic query persisted in ' . $table);
  }
  echo "PASS: synthetic form query absent from $table\n";
}
