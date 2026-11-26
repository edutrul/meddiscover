<?php

namespace Drupal\med_search\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;

/**
 * Theme hooks for search results.
 */
final class SearchThemeHooks {

  /**
   * Parts of a result with their own template variable.
   */
  public const MED_PARTS = ['title', 'description', 'score', 'source', 'activation'];

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'med_search_result' => [
        'render element' => 'element',
      ],
      // Compare page: Developer mode breakdown of the homepage decision.
      'med_compare_tech' => [
        'variables' => ['path' => 'hybrid', 'data' => []],
      ],
      // Compare page: flow diagram of the homepage decision.
      'med_compare_flow' => [
        'variables' => ['query' => '', 'path' => 'hybrid', 'images' => ''],
      ],
    ];
  }

  /**
   * Implements hook_preprocess_HOOK() for med_compare_tech.
   *
   * Exposes each live value as its own variable, so the template's
   * {% trans %} blocks can use simple placeholders.
   */
  #[Hook('preprocess_med_compare_tech')]
  public function preprocessCompareTech(array &$variables): void {
    $variables += $variables['data'];
  }

  /**
   * Implements hook_preprocess_HOOK() for med_search_result.
   *
   * Exposes named parts so themes can map them to components; any other
   * child (enrichment sections, parts added by other modules) is in details.
   */
  #[Hook('preprocess_med_search_result')]
  public function preprocessResult(array &$variables): void {
    $element = $variables['element'];
    foreach (self::MED_PARTS as $part) {
      $variables[$part] = $element[$part] ?? NULL;
    }
    $variables['next_steps'] = $variables['activation'];
    $variables['details'] = [];
    foreach (Element::children($element, TRUE) as $key) {
      if (!in_array($key, self::MED_PARTS, TRUE)) {
        $variables['details'][$key] = $element[$key];
      }
    }
    $variables['attributes']['class'][] = 'med-result';
    $variables['attributes']['data-med-topic-groups'] = implode(' ', $element['#med_topic_groups'] ?? []);
  }

}
