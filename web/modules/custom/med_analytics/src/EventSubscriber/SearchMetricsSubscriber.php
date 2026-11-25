<?php

namespace Drupal\med_analytics\EventSubscriber;

use Drupal\med_analytics\AnalyticsRecorder;
use Drupal\med_search\Event\SearchCompletedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Turns completed searches into aggregate counters and latency sums.
 */
class SearchMetricsSubscriber implements EventSubscriberInterface {

  public function __construct(private AnalyticsRecorder $recorder) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [SearchCompletedEvent::NAME => 'onSearchCompleted'];
  }

  /**
   * Record one search: volume, outcome, errors and stage latencies.
   */
  public function onSearchCompleted(SearchCompletedEvent $event): void {
    $this->recorder->record('search_performed', $event->strategy);
    if ($event->failed) {
      $this->recorder->record('search_error', $event->strategy);
    }
    elseif ($event->resultCount === 0 && $event->answerStatus !== 'urgent') {
      $this->recorder->record('search_no_results', $event->strategy);
    }
    if ($event->answerStatus !== NULL) {
      $this->recorder->record('answer_outcome', $event->answerStatus);
    }
    foreach ($event->timings as $stage => $milliseconds) {
      $this->recorder->recordLatency($event->strategy, $stage, (float) $milliseconds);
    }
  }

}
