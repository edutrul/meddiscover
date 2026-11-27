<?php

namespace Drupal\med_content\Hook;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Migration adjustments for moderated MedlinePlus content.
 */
final class MigrationHooks {

  public function __construct(private ModuleHandlerInterface $moduleHandler) {}

  /**
   * Implements hook_migration_plugins_alter().
   *
   * With content moderation enabled, new imports are published and updates
   * keep the existing publication and moderation state, including archived
   * items.
   */
  #[Hook('migration_plugins_alter')]
  public function migrationPluginsAlter(array &$definitions): void {
    if (!$this->moduleHandler->moduleExists('content_moderation')) {
      return;
    }
    foreach (['med_topics', 'med_relationships', 'med_resources'] as $id) {
      $definition = &$definitions[$id];
      $definition['destination']['overwrite_properties'] = array_values(array_diff(
        array_keys($definition['process']),
        ['status'],
      ));
      $definition['process']['moderation_state'] = [
        'plugin' => 'default_value',
        'default_value' => 'published',
      ];
    }
  }

}
