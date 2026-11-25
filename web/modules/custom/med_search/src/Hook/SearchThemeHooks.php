<?php

namespace Drupal\med_search\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;

/**
 * Theme hooks for search results.
 */
class SearchThemeHooks {

  /**
   * Parts of a result with their own template variable.
   */
  public const PARTS = ['title', 'description', 'score', 'source', 'activation'];

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'med_search_result' => [
        'render element' => 'element',
      ],
    ];
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
    foreach (self::PARTS as $part) {
      $variables[$part] = $element[$part] ?? NULL;
    }
    $variables['next_steps'] = $variables['activation'];
    $variables['details'] = [];
    foreach (Element::children($element, TRUE) as $key) {
      if (!in_array($key, self::PARTS, TRUE)) {
        $variables['details'][$key] = $element[$key];
      }
    }
    $variables['attributes']['class'][] = 'med-result';
  }

}
