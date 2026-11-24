<?php

/**
 * @file
 * Export entity field readback for independent XML verification (local only).
 */

$output = ['nodes' => [], 'terms' => []];
foreach (['node' => ['med_health_topic', 'med_resource', 'med_relationship'], 'taxonomy_term' => ['med_groups', 'med_mesh', 'med_resource_categories']] as $type => $bundles) {
  $storage = \Drupal::entityTypeManager()->getStorage($type);
  $bundle_key = $type === 'node' ? 'type' : 'vid';
  $ids = $storage->getQuery()->accessCheck(FALSE)->condition($bundle_key, $bundles, 'IN')->execute();
  foreach ($storage->loadMultiple($ids) as $entity) {
    $fields = [];
    foreach ($entity->getFields() as $name => $field) {
      if (str_starts_with($name, 'field_med_')) {
        $fields[$name] = $field->getValue();
      }
    }
    $row = ['id' => $entity->id(), 'bundle' => $entity->bundle(), 'label' => $entity->label(), 'language' => $entity->language()->getId(), 'fields' => $fields];
    $output[$type === 'node' ? 'nodes' : 'terms'][] = $row;
  }
}
echo json_encode($output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
