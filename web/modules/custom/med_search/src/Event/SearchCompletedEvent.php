<?php

namespace Drupal\med_search\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched once per search request, after results and enrichment are built.
 *
 * Carries only aggregate-safe facts. It never includes the query text, topic
 * identifiers or URLs, so subscribers cannot record them by accident.
 */
final class SearchCompletedEvent extends Event {

  public const NAME = 'med_search.search_completed';

  /**
   * Constructs the event.
   *
   * @param string $strategy
   *   Strategy ID: semantic, lexical, hybrid or conversational.
   * @param int $resultCount
   *   Number of topics shown.
   * @param bool $failed
   *   Whether the search backend failed.
   * @param string|null $answerStatus
   *   Conversational answer status (answered, abstained, withheld,
   *   unavailable, urgent), or NULL for other strategies.
   * @param array $timings
   *   Milliseconds by stage: total, embedding, retrieval, enrichment,
   *   generation. Only measured stages are present.
   */
  public function __construct(
    public readonly string $strategy,
    public readonly int $resultCount,
    public readonly bool $failed,
    public readonly ?string $answerStatus,
    public readonly array $timings,
  ) {}

}
