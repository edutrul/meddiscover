<?php

namespace Drupal\med_search;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Decides what the homepage search box does with an input.
 *
 * One deterministic rule, no model: urgent wording first (safety message),
 * then questions go to Ask (RAG) and everything else to Hybrid. Instant and
 * predictable; the trade-off is that a question written without a question
 * word ("HbA1c normal range") is treated as a topic search.
 */
final class HomepageRouter {

  use StringTranslationTrait;

  /**
   * Homepage route for urgent wording: a fixed safety message.
   */
  public const MED_URGENT = 'urgent';

  /**
   * Homepage route for questions: a cited answer (RAG).
   */
  public const MED_ASK = 'ask';

  /**
   * Homepage route for topic phrases: a Hybrid topic list.
   */
  public const MED_HYBRID = 'hybrid';

  /**
   * Question syntax, case-insensitive. Shared with the loading indicator JS.
   */
  public const MED_QUESTION_PATTERN = '\?$|^(?:what|why|when|where|who|which|how)\s+\S|^(?:can|could|should|do|does|did|is|are|will)\s+(?:i|we|you|it|there|my|the)\b';

  public function __construct(private UrgencyDetector $urgency) {}

  /**
   * The homepage route for an input: one of the MED_* route constants.
   */
  public function route(string $query): string {
    return match (TRUE) {
      $this->urgency->detect($query) !== NULL => self::MED_URGENT,
      $this->isQuestion($query) => self::MED_ASK,
      default => self::MED_HYBRID,
    };
  }

  /**
   * The urgent group ("emergency" or "crisis") the input matched, if any.
   */
  public function urgency(string $query): ?string {
    return $this->urgency->detect($query);
  }

  /**
   * Whether the input reads as a question.
   */
  public function isQuestion(string $query): bool {
    return (bool) preg_match('/' . self::MED_QUESTION_PATTERN . '/iu', trim($query));
  }

  /**
   * Which part of the question pattern matched, for explanations.
   *
   * Kept next to the pattern so the explanation cannot drift from the rule.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   A phrase such as "starts with the question word “what”", or NULL when
   *   the input is not a question.
   */
  public function questionReason(string $query): ?TranslatableMarkup {
    $query = trim($query);
    if (preg_match('/\?$/u', $query)) {
      return $this->t('ends with a question mark');
    }
    if (preg_match('/^(what|why|when|where|who|which|how)\s+\S/iu', $query, $match)) {
      return $this->t('starts with the question word “@word”', ['@word' => mb_strtolower($match[1])]);
    }
    if (preg_match('/^(can|could|should|do|does|did|is|are|will)\s+(i|we|you|it|there|my|the)\b/iu', $query, $match)) {
      return $this->t('starts with “@words”, a question opener', ['@words' => mb_strtolower($match[1] . ' ' . $match[2])]);
    }
    return NULL;
  }

}
