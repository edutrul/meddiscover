<?php

/**
 * @file
 * Post-updates for medDiscover content.
 */

use Drupal\med_content\Hook\TopicAliasHooks;

/**
 * Add readable aliases to existing published health topics.
 */
function med_content_post_update_topic_aliases(array &$sandbox): void {
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  $sandbox['last_id'] ??= 0;
  $sandbox['total'] ??= (int) $storage->getQuery()->accessCheck(FALSE)
    ->condition('type', 'med_health_topic')->condition('status', 1)
    ->condition('langcode', 'en')->count()->execute();
  $sandbox['processed'] ??= 0;
  $ids = $storage->getQuery()->accessCheck(FALSE)
    ->condition('type', 'med_health_topic')->condition('status', 1)
    ->condition('langcode', 'en')->condition('nid', $sandbox['last_id'], '>')
    ->sort('nid')->range(0, 50)->execute();
  $aliases = new TopicAliasHooks();
  foreach ($storage->loadMultiple($ids) as $topic) {
    $aliases->ensureAlias($topic);
  }
  if ($ids) {
    $sandbox['last_id'] = max($ids);
    $sandbox['processed'] += count($ids);
  }
  $sandbox['#finished'] = count($ids) < 50 ? 1 : min(0.99, $sandbox['processed'] / max(1, $sandbox['total']));
}
