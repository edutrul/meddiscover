<?php

namespace Drupal\med_search\Render;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Pure helpers that mark search links; no services, no state.
 */
final class SearchLinks {

  /**
   * Mark a link for aggregate click counting (inert without med_analytics).
   */
  public static function track(array $link, string $event, string $dimension): array {
    $link['#attributes']['data-med-event'] = $event;
    $link['#attributes']['data-med-dimension'] = $dimension;
    return $link;
  }

  /**
   * Coarse rank bucket, so clicks never identify a specific result.
   */
  public static function rankBucket(int $rank): string {
    return $rank === 1 ? 'rank1' : ($rank <= 3 ? 'rank2-3' : 'rank4plus');
  }

  /**
   * Mark an off-site link: icon via the theme, hidden text for screen readers.
   */
  public static function external(array $link): array {
    $link['#attributes']['class'][] = 'med-link--external';
    $link['#attributes']['rel'] = 'noopener noreferrer';
    $link['#attributes']['target'] = '_blank';
    $link['#suffix'] = '<span class="visually-hidden"> ' . new TranslatableMarkup('(opens in a new tab)') . '</span>' . ($link['#suffix'] ?? '');
    return $link;
  }

  /**
   * External link to an original source URL.
   */
  public static function link(string|\Stringable $title, string $url): array {
    return self::external(['#type' => 'link', '#title' => $title, '#url' => Url::fromUri($url)]);
  }

}
