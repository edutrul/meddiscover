<?php

namespace Drupal\med_search;

/**
 * Turns text into vectors for semantic search.
 *
 * Implementations must never log or echo the input: queries are health
 * questions.
 */
interface EmbedderInterface {

  /**
   * The embedding model ID, for display and stored-vector metadata.
   */
  public function modelId(): string;

  /**
   * Fail unless the pinned embedding model is the one in use.
   *
   * @throws \RuntimeException
   *   When the provider is unavailable or the model changed.
   */
  public function verifyModel(): void;

  /**
   * The exact text embedded for a search query (model-specific prefix).
   */
  public function queryInput(string $query): string;

  /**
   * A validated query vector.
   *
   * @throws \RuntimeException
   *   With a generic message, never containing the query.
   */
  public function embedQuery(string $query): array;

  /**
   * A validated vector for already formatted input (for example a topic).
   *
   * @throws \RuntimeException
   *   With a generic message, never containing the input.
   */
  public function embed(string $text): array;

}
