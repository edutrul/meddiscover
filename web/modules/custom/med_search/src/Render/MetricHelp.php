<?php

namespace Drupal\med_search\Render;

/**
 * A short label with help shown on hover or as a disclosure.
 */
final class MetricHelp {

  /**
   * Builds the help disclosure.
   */
  public static function build(string|\Stringable $label, string|\Stringable $help): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'details',
      '#attributes' => ['class' => ['med-metric-help']],
      'summary' => [
        '#type' => 'html_tag',
        '#tag' => 'summary',
        '#value' => $label,
        '#attributes' => ['data-help' => $help],
      ],
      'explanation' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $help,
      ],
    ];
  }

}
