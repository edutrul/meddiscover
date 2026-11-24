<?php

namespace Drupal\med_search;

/**
 * Structured text generation used by conversational search.
 *
 * Implementations must never log or echo prompts, because they contain the
 * user's health question.
 */
interface GeneratorInterface {

  /**
   * Generate one JSON object matching $schema.
   *
   * @param string $system
   *   Fixed instructions.
   * @param string $user
   *   Question plus retrieved sources.
   * @param array $schema
   *   JSON schema the output must follow.
   *
   * @return array
   *   The decoded object.
   *
   * @throws \RuntimeException
   *   With a generic message when the provider fails, times out, truncates
   *   its output or returns invalid JSON.
   */
  public function generate(string $system, string $user, array $schema): array;

}
