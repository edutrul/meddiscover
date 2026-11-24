<?php

/**
 * @file
 * Synthetic integration checks; refuses to run over existing medDiscover nodes.
 */

use Drupal\med_content\Corpus;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationInterface;

function med_expect(bool $condition, string $message): void {
  if (!$condition) {
    throw new RuntimeException($message);
  }
}
$storage = \Drupal::entityTypeManager()->getStorage('node');
$bundles = ['med_health_topic', 'med_resource', 'med_relationship'];
med_expect((int) $storage->getQuery()->accessCheck(FALSE)->condition('type', $bundles, 'IN')->count()->execute() === 0,
  'Fixture checks require an empty test database; never run on the imported corpus.');
$relative_path = 'web/modules/custom/med_content/tests/fixtures/topics.xml';
$path = dirname(DRUPAL_ROOT) . '/' . $relative_path;
$hash = hash_file('sha256', $path);
$selection = ['test-1', 'test-2'];
foreach ([['wrong checksum', str_repeat('0', 64), $selection], ['Spanish selection', $hash, ['test-es']]] as [$name, $expected_hash, $ids]) {
  $rejected = FALSE;
  try {
    Corpus::load($path, $expected_hash, $ids);
  }
  catch (RuntimeException) {
    $rejected = TRUE;
  }
  med_expect($rejected, "Must reject $name");
}
med_expect(Corpus::plain('<p>First</p><p>Second</p>') === 'First Second', 'Block boundaries must retain word separation');
$safe = Corpus::sanitize('<p style="color:red" onclick="bad()">Text<a href="javascript:bad()">bad</a><a href="//evil.test/x">bad</a><a href="/safe.html">safe</a><iframe src="https://example.org"></iframe><script>bad()</script></p>');
med_expect(!preg_match('/<(script|iframe)|on[a-z]+\s*=|style\s*=|javascript:/i', $safe), 'Unsafe HTML survived');
med_expect(str_contains($safe, 'href="https://medlineplus.gov/safe.html"'), 'Safe relative link lost');
$malicious_path = tempnam(sys_get_temp_dir(), 'med-entity-');
file_put_contents($malicious_path, '<!DOCTYPE x [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><health-topics/>');
try {
  $rejected = FALSE;
  try {
    Corpus::load($malicious_path, hash_file('sha256', $malicious_path), $selection);
  }
  catch (RuntimeException) {
    $rejected = TRUE;
  }
  med_expect($rejected, 'Entity declaration must be rejected');
}
finally {
  unlink($malicious_path);
}
$manager = \Drupal::service('plugin.manager.migration');
$order = ['med_groups', 'med_mesh', 'med_categories', 'med_topics', 'med_resources', 'med_relationships'];
function med_import_fixture($manager, array $order, string $path, string $hash, array $selection): void {
  foreach ($order as $id) {
    $migration = $manager->createInstance($id);
    $migration->set('source', array_replace($migration->getSourceConfiguration(), [
      'path' => $path, 'sha256' => $hash, 'selection' => $selection,
    ]));
    // Dependency order is explicit above. Production dependency counts describe
    // the 24-topic snapshot, so they do not apply to the two-topic fixture.
    $migration->set('requirements', []);
    $result = (new MigrateExecutable($migration))->import();
    med_expect($result === MigrationInterface::RESULT_COMPLETED, "Migration failed: $id");
    med_expect($migration->getIdMap()->errorCount() === 0, "Row errors: $id");
  }
}
med_import_fixture($manager, $order, $relative_path, $hash, $selection);
$ids = $storage->getQuery()->accessCheck(FALSE)->condition('type', $bundles, 'IN')->execute();
$before = [];
$counts = [];
foreach ($storage->loadMultiple($ids) as $node) {
  $before[$node->id()] = $node->toArray();
  $counts[$node->bundle()] = ($counts[$node->bundle()] ?? 0) + 1;
  // Text-format access validation must use the intended administrator account.
  $switcher = \Drupal::service('account_switcher');
  $switcher->switchTo(\Drupal\user\Entity\User::load(1));
  try {
    med_expect(count($node->validate()) === 0, 'Invalid migrated entity');
  }
  finally {
    $switcher->switchBack();
  }
}
med_expect(($counts['med_health_topic'] ?? 0) === 2, 'Only the two English fixture topics must import');
med_expect(($counts['med_resource'] ?? 0) === 2, 'Resource assignments must retain the repeated URL');
med_expect(($counts['med_relationship'] ?? 0) === 5, 'All typed fixture relationships must import');
$topic_ids = $storage->getQuery()->accessCheck(FALSE)->condition('type', 'med_health_topic')->condition('field_med_source_id', 'test-1')->execute();
$topic = $storage->load(reset($topic_ids));
med_expect($topic->field_med_also_called->value === 'Fixture Alias' && $topic->field_med_see_reference->value === 'Ambiguous Fixture', 'Alias types lost');
med_expect(str_contains($topic->field_med_summary_raw->value, '<script>'), 'Original markup not preserved');
med_expect(!str_contains($topic->field_med_summary_html->value, '<script>'), 'Rendered summary unsafe');
$heading = $topic->field_med_mesh->entity;
med_expect($heading->field_med_source_id->value === 'DTEST' && $heading->field_med_qualifier_id->value === 'QTEST', 'Descriptor/qualifier pairing lost');
foreach ($storage->loadMultiple($ids) as $node) {
  if ($node->bundle() === 'med_relationship') {
    $target_id = $node->field_med_target_source_id->value;
    med_expect($target_id === 'test-2' ? !$node->field_med_target->isEmpty() : $node->field_med_target->isEmpty(), 'External target created a stub');
    med_expect(!$node->field_med_target_url->isEmpty(), 'External source URL missing');
    if ($node->field_med_relation_type->value === 'language_mapping') {
      med_expect(str_contains($node->label(), 'External language mapping') && !str_contains($node->label(), 'Synthetic Spanish'), 'Language-mapping label must use English interface text');
      med_expect($node->field_med_target_title->value === 'Synthetic Spanish', 'Original external-language metadata must remain preserved');
    }
  }
}
med_import_fixture($manager, $order, $relative_path, $hash, $selection);
$storage->resetCache();
$after_ids = $storage->getQuery()->accessCheck(FALSE)->condition('type', $bundles, 'IN')->execute();
med_expect(array_values($ids) === array_values($after_ids), 'Rerun created duplicates');
foreach ($storage->loadMultiple($after_ids) as $node) {
  med_expect($before[$node->id()] === $node->toArray(), 'Rerun changed entity data');
}
echo "PASS: fixture extraction, integrity/language/entity rejection, sanitization, references, MeSH pairing, repeated resources and idempotence.\n";
