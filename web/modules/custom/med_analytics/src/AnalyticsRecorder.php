<?php

namespace Drupal\med_analytics;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Stores allowlisted events as daily aggregate counters.
 *
 * Each row is (UTC day, event, dimension) with a count and, for latency, a
 * millisecond sum. Dimensions come from small fixed vocabularies (strategy,
 * rank bucket, activation rule ID), so free text such as a health query or a
 * URL can never be stored. There is no timestamp below a day, and no user,
 * session, IP, topic or destination.
 */
class AnalyticsRecorder {

  public const STRATEGIES = ['semantic', 'lexical', 'hybrid', 'conversational'];

  public const STAGES = ['total', 'embedding', 'retrieval', 'enrichment', 'generation'];

  public const LATENCY_BUCKETS = [100 => 'lt100ms', 500 => 'lt500ms', 1000 => 'lt1s', 5000 => 'lt5s', 15000 => 'lt15s'];

  /**
   * Events visitors' browsers may report (link clicks only).
   */
  public const CLIENT_EVENTS = [
    'search_result_clicked',
    'related_content_clicked',
    'external_resource_clicked',
    'cta_clicked',
    'recommendation_clicked',
  ];

  public function __construct(
    private Connection $database,
    private ConfigFactoryInterface $configFactory,
    private TimeInterface $time,
    private EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Whether an event and dimension pair belongs to the contract.
   */
  public function isAllowed(string $event, string $dimension): bool {
    $strategies = self::STRATEGIES;
    $ranks = ['rank1', 'rank2-3', 'rank4plus'];
    $buckets = [...array_values(self::LATENCY_BUCKETS), 'ge15s'];
    [$prefix, $suffix] = explode(':', $dimension, 2) + [1 => NULL];
    return match ($event) {
      'search_performed', 'search_no_results', 'search_error' => in_array($dimension, $strategies, TRUE),
      'answer_outcome' => in_array($dimension, ['answered', 'abstained', 'withheld', 'unavailable', 'urgent'], TRUE),
      'search_result_clicked' => in_array($prefix, $strategies, TRUE) && in_array($suffix, $ranks, TRUE),
      'related_content_clicked' => in_array($dimension, ['explicit', 'inferred'], TRUE),
      'external_resource_clicked' => in_array($dimension, ['source', 'citation', 'resources', 'further_reading'], TRUE),
      'cta_shown', 'cta_clicked' => $this->isActivationRule($dimension),
      'recommendation_shown', 'recommendation_clicked' => $dimension === 'related_topic',
      'latency' => in_array($prefix, $strategies, TRUE) && in_array($suffix, self::STAGES, TRUE),
      'latency_bucket' => in_array($prefix, $strategies, TRUE) && in_array($suffix, $buckets, TRUE),
      default => FALSE,
    };
  }

  /**
   * Increment today's counter; returns FALSE when disabled or not allowed.
   */
  public function record(string $event, string $dimension = '', ?float $milliseconds = NULL): bool {
    if (!$this->configFactory->get('med_analytics.settings')->get('enabled') || !$this->isAllowed($event, $dimension)) {
      return FALSE;
    }
    $ms = $milliseconds !== NULL && is_finite($milliseconds) && $milliseconds >= 0 ? $milliseconds : 0.0;
    $this->database->merge('med_analytics_daily')
      ->keys(['day' => $this->today(), 'event' => $event, 'dimension' => $dimension])
      ->insertFields([
        'day' => $this->today(),
        'event' => $event,
        'dimension' => $dimension,
        'count' => 1,
        'total_ms' => $ms,
      ])
      ->expression('count', '[count] + 1')
      ->expression('total_ms', '[total_ms] + :ms', [':ms' => $ms])
      ->execute();
    return TRUE;
  }

  /**
   * Record a latency sample: sum per stage and a bucket for the total.
   */
  public function recordLatency(string $strategy, string $stage, float $milliseconds): void {
    $this->record('latency', "$strategy:$stage", $milliseconds);
    if ($stage === 'total') {
      $bucket = 'ge15s';
      foreach (self::LATENCY_BUCKETS as $limit => $name) {
        if ($milliseconds < $limit) {
          $bucket = $name;
          break;
        }
      }
      $this->record('latency_bucket', "$strategy:$bucket");
    }
  }

  /**
   * Delete aggregates older than the retention period.
   */
  public function purge(): int {
    $days = (int) $this->configFactory->get('med_analytics.settings')->get('retention_days');
    $cutoff = gmdate('Y-m-d', $this->time->getRequestTime() - $days * 86400);
    return (int) $this->database->delete('med_analytics_daily')->condition('day', $cutoff, '<')->execute();
  }

  /**
   * Aggregates since a number of days ago, keyed by event then dimension.
   *
   * @return array
   *   [event][dimension] => ['count' => int, 'total_ms' => float].
   */
  public function totals(int $days): array {
    $since = gmdate('Y-m-d', $this->time->getRequestTime() - ($days - 1) * 86400);
    $rows = $this->database->query('SELECT event, dimension, SUM(count) AS count, SUM(total_ms) AS total_ms FROM {med_analytics_daily} WHERE day >= :since GROUP BY event, dimension ORDER BY event, dimension', [':since' => $since]);
    $totals = [];
    foreach ($rows as $row) {
      $totals[$row->event][$row->dimension] = ['count' => (int) $row->count, 'total_ms' => (float) $row->total_ms];
    }
    return $totals;
  }

  /**
   * UTC day of the current request.
   */
  private function today(): string {
    return gmdate('Y-m-d', $this->time->getRequestTime());
  }

  /**
   * Existing activation rule IDs only, when med_activation is installed.
   */
  private function isActivationRule(string $id): bool {
    return preg_match('/^[a-z0-9_]{1,64}$/', $id)
      && $this->entityTypeManager->hasDefinition('med_activation_rule')
      && $this->entityTypeManager->getStorage('med_activation_rule')->load($id) !== NULL;
  }

}
