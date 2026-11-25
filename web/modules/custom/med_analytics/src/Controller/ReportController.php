<?php

namespace Drupal\med_analytics\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\med_analytics\AnalyticsRecorder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Aggregate metrics report with derived rates.
 */
class ReportController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  public function __construct(
    private AnalyticsRecorder $recorder,
    private ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('med_analytics.recorder'), $container->get('config.factory'));
  }

  /**
   * Build the report.
   */
  public function report(): array {
    $days = (int) $this->configFactory->get('med_analytics.settings')->get('report_days');
    $t = $this->recorder->totals($days);
    $count = fn(string $event, string $dimension) => $t[$event][$dimension]['count'] ?? 0;
    // Rates render as a native <meter> bar plus the number, so the value is
    // available to assistive technology and without color.
    $rate = fn(int $part, int $whole) => $whole ? [
      'data' => [
        '#type' => 'inline_template',
        '#template' => '<meter min="0" max="100" value="{{ value }}"></meter> {{ value }} %',
        '#context' => ['value' => number_format(100 * $part / $whole, 1)],
      ],
    ] : '–';

    $build['intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Daily aggregates for the last @days days (UTC). No search text, topic, destination URL, user, session or IP address is stored. Clicks are navigation to educational resources; they are not appointments, treatments or healthcare outcomes.', ['@days' => $days]),
    ];

    $rows = [];
    foreach (AnalyticsRecorder::STRATEGIES as $strategy) {
      $performed = $count('search_performed', $strategy);
      $latency = $t['latency']["$strategy:total"] ?? NULL;
      $rows[] = [
        $strategy,
        $performed,
        $rate($count('search_no_results', $strategy), $performed),
        $rate($count('search_error', $strategy), $performed),
        $latency ? number_format($latency['total_ms'] / $latency['count'], 0) . ' ms' : '–',
        $rate($count('search_result_clicked', "$strategy:rank1") + $count('search_result_clicked', "$strategy:rank2-3") + $count('search_result_clicked', "$strategy:rank4plus"), $performed),
      ];
    }
    $build['search'] = [
      '#type' => 'table',
      '#caption' => $this->t('Search'),
      '#header' => [
        $this->t('Strategy'),
        $this->t('Searches'),
        $this->t('Zero-result rate'),
        $this->t('Error rate'),
        $this->t('Mean end-to-end'),
        $this->t('Result clicks per search'),
      ],
      '#rows' => $rows,
    ];

    $rows = [];
    foreach (AnalyticsRecorder::STRATEGIES as $strategy) {
      foreach (AnalyticsRecorder::STAGES as $stage) {
        if ($sample = $t['latency']["$strategy:$stage"] ?? NULL) {
          $mean = number_format($sample['total_ms'] / $sample['count'], 1) . ' ms';
          $rows[] = [$strategy, $stage, $sample['count'], $mean];
        }
      }
    }
    $build['latency'] = [
      '#type' => 'table',
      '#caption' => $this->t('Latency by stage (mean)'),
      '#header' => [$this->t('Strategy'), $this->t('Stage'), $this->t('Samples'), $this->t('Mean')],
      '#rows' => $rows,
      '#empty' => $this->t('No searches recorded yet.'),
    ];

    $buckets = [...array_values(AnalyticsRecorder::LATENCY_BUCKETS), 'ge15s'];
    $rows = [];
    foreach (AnalyticsRecorder::STRATEGIES as $strategy) {
      $row = [$strategy];
      foreach ($buckets as $bucket) {
        $row[] = $count('latency_bucket', "$strategy:$bucket");
      }
      $rows[] = $row;
    }
    $build['buckets'] = [
      '#type' => 'table',
      '#caption' => $this->t('End-to-end latency distribution (searches)'),
      '#header' => array_merge([$this->t('Strategy')], ['< 100 ms', '< 500 ms', '< 1 s', '< 5 s', '< 15 s', '≥ 15 s']),
      '#rows' => $rows,
    ];

    $answers = $t['answer_outcome'] ?? [];
    $total_answers = array_sum(array_column($answers, 'count'));
    $build['answers'] = [
      '#type' => 'table',
      '#caption' => $this->t('Conversational outcomes'),
      '#header' => [$this->t('Outcome'), $this->t('Count'), $this->t('Share')],
      '#rows' => array_map(fn($outcome) => [
        $outcome,
        $count('answer_outcome', $outcome),
        $rate($count('answer_outcome', $outcome), $total_answers),
      ], ['answered', 'abstained', 'withheld', 'unavailable', 'urgent']),
    ];

    $rows = [];
    foreach (array_unique(array_merge(array_keys($t['cta_shown'] ?? []), array_keys($t['cta_clicked'] ?? []))) as $rule) {
      $shown = $count('cta_shown', $rule);
      $clicked = $count('cta_clicked', $rule);
      $rows[] = [$rule, $shown, $clicked, $rate($clicked, $shown)];
    }
    $shown = $count('recommendation_shown', 'related_topic');
    $clicked = $count('recommendation_clicked', 'related_topic');
    $rows[] = [$this->t('Related topic recommendation'), $shown, $clicked, $rate($clicked, $shown)];
    $build['activation'] = [
      '#type' => 'table',
      '#caption' => $this->t('Activation (impressions are calls to action rendered; clicks are navigation only)'),
      '#header' => [$this->t('Rule'), $this->t('Impressions'), $this->t('Clicks'), $this->t('Click-through rate')],
      '#rows' => $rows,
    ];

    $rows = [];
    $areas = [
      'related_content_clicked' => ['explicit', 'inferred'],
      'external_resource_clicked' => ['source', 'citation', 'resources', 'further_reading'],
    ];
    foreach ($areas as $event => $dimensions) {
      foreach ($dimensions as $dimension) {
        $rows[] = [$event, $dimension, $count($event, $dimension)];
      }
    }
    $build['engagement'] = [
      '#type' => 'table',
      '#caption' => $this->t('Related content and external resources'),
      '#header' => [$this->t('Event'), $this->t('Where'), $this->t('Clicks')],
      '#rows' => $rows,
    ];
    $build['#cache']['max-age'] = 0;
    return $build;
  }

}
