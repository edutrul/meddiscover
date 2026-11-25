<?php

namespace Drupal\med_content\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeInterface;
use Drupal\path_alias\Entity\PathAlias;

/**
 * Creates stable, readable aliases for published health topics.
 */
final class TopicAliasHooks {

  /**
   * Creates an alias when a published topic has none.
   */
  #[Hook('node_insert')]
  #[Hook('node_update')]
  public function ensureAlias(NodeInterface $node): void {
    if ($node->bundle() !== 'med_health_topic' || !$node->isPublished()
      || !$node->isDefaultRevision() || $node->language()->getId() !== 'en') {
      return;
    }

    $manager = \Drupal::service('path_alias.manager');
    $path = '/node/' . $node->id();
    if ($manager->getAliasByPath($path, 'en') !== $path) {
      return;
    }

    $text = \Drupal::transliteration()->transliterate($node->label(), 'en');
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($text)), '-');
    $slug = rtrim(substr($slug, 0, 180), '-') ?: 'topic';
    $alias = '/health-topics/' . $slug;
    $candidate = $alias;
    $suffix = (int) $node->id();
    while ($manager->getPathByAlias($candidate, 'en') !== $candidate) {
      $candidate = $alias . '-' . $suffix++;
    }

    PathAlias::create(['path' => $path, 'alias' => $candidate, 'langcode' => 'en'])->save();
  }

}
