<?php

namespace Drupal\med_search;

/**
 * Shared query validation for all strategies.
 */
final class QueryText {

  public const MED_MAX_LENGTH = 500;

  public const MED_MAX_LIMIT = 20;

  /**
   * Trim and validate user query text and the requested result limit.
   */
  public static function validate(string $query, int $limit): string {
    $query = trim($query);
    if ($query === '' || mb_strlen($query) > self::MED_MAX_LENGTH) {
      throw new \InvalidArgumentException('Enter a query between 1 and 500 characters.');
    }
    if ($limit < 1 || $limit > self::MED_MAX_LIMIT) {
      throw new \InvalidArgumentException('Invalid result limit.');
    }
    return $query;
  }

  /**
   * Compare names case- and punctuation-insensitively ("Hemoglobin A1c").
   */
  public static function normalizeName(string $text): string {
    return trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($text)));
  }

}
