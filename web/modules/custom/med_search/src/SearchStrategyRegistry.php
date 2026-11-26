<?php

namespace Drupal\med_search;

/**
 * Collects every service tagged "med_search.strategy", keyed by its ID.
 */
final class SearchStrategyRegistry {

  /**
   * Strategies keyed by ID.
   *
   * @var \Drupal\med_search\SearchStrategyInterface[]
   */
  private array $strategies = [];

  /**
   * Service collector callback.
   */
  public function addStrategy(SearchStrategyInterface $strategy): void {
    $this->strategies[$strategy->id()] = $strategy;
  }

  /**
   * Get a strategy by ID.
   *
   * @throws \InvalidArgumentException
   *   For an unknown ID.
   */
  public function get(string $id): SearchStrategyInterface {
    return $this->strategies[$id] ?? throw new \InvalidArgumentException('Unknown search strategy.');
  }

  /**
   * Registered strategy IDs.
   */
  public function ids(): array {
    return array_keys($this->strategies);
  }

}
