<?php

/**
 * @file
 * Assert foundation behavior through Drupal's actual database connection.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();
if ($connection->driver() !== 'pgsql') {
  throw new RuntimeException('Drupal must use PostgreSQL.');
}
foreach (['pg_trgm', 'vector'] as $extension) {
  if (!$connection->query('SELECT COUNT(*) FROM pg_extension WHERE extname = :name', [':name' => $extension])->fetchField()) {
    throw new RuntimeException("Missing extension: $extension");
  }
}
$distance = (float) $connection->query("SELECT CAST(:a AS vector) <=> CAST(:b AS vector)", [':a' => '[1,0,0]', ':b' => '[0,1,0]'])->fetchField();
if (abs($distance - 1.0) > 0.000001) {
  throw new RuntimeException('Orthogonal vector cosine distance must be 1.');
}
$admin = \Drupal::entityTypeManager()->getStorage('user')->load(1);
if (!$admin || !$admin->isActive() || !$admin->hasPermission('administer site configuration')) {
  throw new RuntimeException('Active administrator account is required.');
}
echo "PASS: Drupal PostgreSQL connection, pg_trgm, vector, cosine distance and admin permissions.\n";
