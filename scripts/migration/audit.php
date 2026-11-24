<?php

/**
 * @file
 * Validate every imported entity and distinguish duplicate labels/edge identity.
 */

$manager = \Drupal::entityTypeManager();
$switcher = \Drupal::service('account_switcher');
$switcher->switchTo(\Drupal\user\Entity\User::load(1));
$counts = $titles = $edges = [];
try {
  foreach (['node' => ['med_health_topic', 'med_resource', 'med_relationship'], 'taxonomy_term' => ['med_groups', 'med_mesh', 'med_resource_categories']] as $type => $bundles) {
    $storage = $manager->getStorage($type);
    $ids = $storage->getQuery()->accessCheck(FALSE)->condition($type === 'node' ? 'type' : 'vid', $bundles, 'IN')->execute();
    foreach ($storage->loadMultiple($ids) as $entity) {
      $violations = $entity->validate();
      if (count($violations)) {
        throw new RuntimeException('Invalid ' . $type . ' ' . $entity->id() . ': ' . (string) $violations);
      }
      if ($entity->language()->getId() !== 'en') {
        throw new RuntimeException('Non-English imported entity: ' . $entity->id());
      }
      $counts[$entity->bundle()] = ($counts[$entity->bundle()] ?? 0) + 1;
      if ($entity->bundle() === 'med_relationship') {
        $titles[$entity->label()] = ($titles[$entity->label()] ?? 0) + 1;
        $key = $entity->field_med_topic->target_id . '|' . $entity->field_med_relation_type->value . '|' . $entity->field_med_target_url->uri;
        $edges[$key] = ($edges[$key] ?? 0) + 1;
        if ($entity->field_med_relation_type->value === 'language_mapping' && !$entity->field_med_target->isEmpty()) {
          throw new RuntimeException('External language mapping must not reference an imported Spanish topic.');
        }
      }
    }
  }
}
finally {
  $switcher->switchBack();
}
$duplicate_edges = count(array_filter($edges, fn($n) => $n > 1));
if (count(array_filter($titles, fn($n) => $n > 1))) {
  throw new RuntimeException('Ambiguous relationship labels remain.');
}
if ($duplicate_edges) {
  throw new RuntimeException('Repeated same-source, same-target and same-type relationship requires review.');
}
echo json_encode(['status' => 'passed', 'entity_counts' => $counts,
  'validated_entities' => array_sum($counts), 'duplicate_relationship_title_groups' => count(array_filter($titles, fn($n) => $n > 1)),
  'duplicate_source_target_type_groups' => $duplicate_edges,
  'english_health_topics' => $counts['med_health_topic']], JSON_PRETTY_PRINT) . "\n";
