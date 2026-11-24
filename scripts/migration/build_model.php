<?php

/**
 * @file
 * Development bootstrap for the exported core entity/field configuration.
 *
 * Run once with Drush after enabling med_content; fresh sites use config sync.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;

function med_field(string $entity, string $bundle, string $name, string $label, string $type = 'string', int $cardinality = 1, array $settings = [], array $storage_settings = []): void {
  $storage = FieldStorageConfig::loadByName($entity, $name);
  if (!$storage) {
    $storage = FieldStorageConfig::create([
      'entity_type' => $entity, 'field_name' => $name, 'type' => $type,
      'cardinality' => $cardinality, 'settings' => $storage_settings,
    ]);
    $storage->save();
  }
  if (!FieldConfig::loadByName($entity, $bundle, $name)) {
    FieldConfig::create([
      'entity_type' => $entity, 'bundle' => $bundle, 'field_name' => $name,
      'label' => $label, 'settings' => $settings,
    ])->save();
  }
  $form_id = "$entity.$bundle.default";
  $form = EntityFormDisplay::load($form_id) ?: EntityFormDisplay::create([
    'targetEntityType' => $entity, 'bundle' => $bundle, 'mode' => 'default', 'status' => TRUE,
  ]);
  $widgets = ['string_long' => 'string_textarea', 'text_long' => 'text_textarea',
    'entity_reference' => 'entity_reference_autocomplete', 'link' => 'link_default',
    'datetime' => 'datetime_default'];
  $form->setComponent($name, ['type' => $widgets[$type] ?? 'string_textfield'])->save();
  $view = EntityViewDisplay::load($form_id) ?: EntityViewDisplay::create([
    'targetEntityType' => $entity, 'bundle' => $bundle, 'mode' => 'default', 'status' => TRUE,
  ]);
  if (in_array($name, ['field_med_summary_raw', 'field_med_summary_plain', 'field_med_key'], TRUE)) {
    $view->removeComponent($name);
  }
  else {
    $formatters = ['string_long' => 'basic_string', 'text_long' => 'text_default',
      'entity_reference' => 'entity_reference_label', 'link' => 'link', 'datetime' => 'datetime_default'];
    $view->setComponent($name, ['type' => $formatters[$type] ?? 'string', 'label' => 'above']);
  }
  $view->save();
}
function med_ref(string $bundle, string $field, string $label, string $target, string $target_bundle, int $cardinality = -1): void {
  med_field('node', $bundle, $field, $label, 'entity_reference', $cardinality,
    ['handler' => 'default:' . $target, 'handler_settings' => ['target_bundles' => [$target_bundle => $target_bundle]]],
    ['target_type' => $target]);
}
foreach (['med_health_topic' => 'Health Topic', 'med_resource' => 'Resource Assignment', 'med_relationship' => 'Topic Relationship'] as $id => $label) {
  if (!NodeType::load($id)) {
    NodeType::create(['type' => $id, 'name' => $label, 'new_revision' => TRUE, 'display_submitted' => FALSE])->save();
  }
  med_field('node', $id, 'field_med_key', 'Migration identifier');
}
foreach (['med_groups' => 'Medical Groups', 'med_mesh' => 'MeSH Headings', 'med_resource_categories' => 'Resource Categories'] as $id => $label) {
  if (!Vocabulary::load($id)) {
    Vocabulary::create(['vid' => $id, 'name' => $label])->save();
  }
  med_field('taxonomy_term', $id, 'field_med_key', 'Migration identifier');
}
foreach (['med_groups', 'med_mesh'] as $vid) {
  med_field('taxonomy_term', $vid, 'field_med_source_id', 'Source identifier');
}
med_field('taxonomy_term', 'med_groups', 'field_med_source_url', 'Source URL', 'link', 1, ['link_type' => 16, 'title' => 0]);
foreach (['descriptor_label' => 'Descriptor label', 'qualifier_id' => 'Qualifier ID', 'qualifier_label' => 'Qualifier label'] as $suffix => $label) {
  med_field('taxonomy_term', 'med_mesh', 'field_med_' . $suffix, $label);
}
$b = 'med_health_topic';
foreach (['source_id' => 'MedlinePlus ID', 'attribution' => 'Source attribution'] as $suffix => $label) {
  med_field('node', $b, 'field_med_' . $suffix, $label);
}
foreach (['description' => 'Meta description', 'summary_raw' => 'Original summary (source markup)', 'summary_plain' => 'Plain-text summary'] as $suffix => $label) {
  med_field('node', $b, 'field_med_' . $suffix, $label, 'string_long');
}
med_field('node', $b, 'field_med_summary_html', 'Sanitized summary', 'text_long');
foreach (['also_called' => 'Also called', 'see_reference' => 'See reference'] as $suffix => $label) {
  med_field('node', $b, 'field_med_' . $suffix, $label, 'string', -1);
}
med_field('node', $b, 'field_med_source_url', 'Source URL', 'link', 1, ['link_type' => 16, 'title' => 0]);
foreach (['source_date' => 'Source creation date', 'snapshot_date' => 'Dataset snapshot date'] as $suffix => $label) {
  med_field('node', $b, 'field_med_' . $suffix, $label, 'datetime', 1, [], ['datetime_type' => 'date']);
}
med_ref($b, 'field_med_groups', 'Medical groups', 'taxonomy_term', 'med_groups');
med_ref($b, 'field_med_mesh', 'MeSH headings', 'taxonomy_term', 'med_mesh');
foreach (['med_resource', 'med_relationship'] as $bundle) {
  med_ref($bundle, 'field_med_topic', 'Source health topic', 'node', 'med_health_topic', 1);
}
$b = 'med_resource';
foreach (['url' => 'Resource URL', 'mapped_url' => 'Language-mapped URL'] as $suffix => $label) {
  med_field('node', $b, 'field_med_' . $suffix, $label, 'link', 1, ['link_type' => 16, 'title' => 0]);
}
foreach (['organizations' => 'Organizations', 'standard_desc' => 'Standard descriptions'] as $suffix => $label) {
  med_field('node', $b, 'field_med_' . $suffix, $label, 'string_long', -1);
}
med_ref($b, 'field_med_categories', 'Resource categories', 'taxonomy_term', 'med_resource_categories');
$b = 'med_relationship';
med_ref($b, 'field_med_target', 'Imported target topic (when in corpus)', 'node', 'med_health_topic', 1);
foreach (['relation_type' => 'Relationship type', 'provenance' => 'Relationship provenance', 'target_source_id' => 'Target source ID', 'target_title' => 'Target source title (original language)'] as $suffix => $label) {
  med_field('node', $b, 'field_med_' . $suffix, $label);
}
med_field('node', $b, 'field_med_target_url', 'Target source URL', 'link', 1, ['link_type' => 16, 'title' => 0]);
if (!FilterFormat::load('med_source_html')) {
  FilterFormat::create([
    'format' => 'med_source_html', 'name' => 'MedlinePlus source HTML', 'status' => TRUE,
    'filters' => ['filter_html' => ['status' => TRUE, 'settings' => [
      'allowed_html' => '<p> <a href title> <ul> <ol> <li> <strong> <em> <b> <i> <br> <h2> <h3> <h4> <blockquote>',
      'filter_html_help' => FALSE, 'filter_html_nofollow' => TRUE,
    ]]],
  ])->save();
}
echo "Content model configuration created; export and review config/sync.\n";
