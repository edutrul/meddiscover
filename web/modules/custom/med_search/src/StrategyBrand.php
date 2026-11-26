<?php

namespace Drupal\med_search;

/**
 * Provides the technology marks shared by the strategy explanations.
 */
final class StrategyBrand {

  /**
   * Public URL path of the module's images directory, with a trailing slash.
   */
  public static function imagePath(): string {
    return base_path() . \Drupal::service('extension.list.module')->getPath('med_search') . '/images/';
  }

  /**
   * Builds the marks for a retrieval strategy.
   */
  public static function build(string $strategy): array {
    if (!in_array($strategy, ['lexical', 'semantic', 'hybrid'], TRUE)) {
      return [];
    }
    $path = self::imagePath();
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['med-strategy-brand']],
    ];
    if ($strategy !== 'semantic') {
      // Official mark from https://solr.apache.org/logos-and-assets.html.
      $build['solr'] = ['#theme' => 'image', '#uri' => $path . 'solr.png', '#alt' => 'Apache Solr'];
    }
    if ($strategy === 'hybrid') {
      $build['plus'] = ['#plain_text' => '+'];
    }
    if ($strategy !== 'lexical') {
      // Official elephant from https://wiki.postgresql.org/wiki/Logo.
      $build['postgresql'] = ['#theme' => 'image', '#uri' => $path . 'postgresql.svg', '#alt' => 'PostgreSQL'];
      $build['extension'] = ['#plain_text' => 'pgvector'];
    }
    return $build;
  }

}
