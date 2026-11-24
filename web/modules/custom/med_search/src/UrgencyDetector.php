<?php

namespace Drupal\med_search;

/**
 * Deterministic detection of possibly urgent situations.
 *
 * Runs before retrieval and generation: an emergency must never depend on
 * similarity ranking or a language model. The patterns favor recall; a false
 * positive only adds a safety message.
 */
class UrgencyDetector {

  /**
   * Pattern groups. Crisis is checked first.
   */
  public const PATTERNS = [
    'crisis' => [
      '/\b(kill(ing)? myself|suicid\w*|end (my|it all)|take my (own )?life|self[- ]?harm|hurt(ing)? myself|want to die|better off dead)\b/i',
      '/\b(overdos\w*|took too many (pills|tablets))\b/i',
    ],
    'emergency' => [
      '/\b(can\'?t|cannot|unable to|hard to|trouble|difficulty|struggling to) (breathe|breathing|swallow)\b/i',
      '/\b(throat|tongue|lips?|face) (is |are )?(swell|swells|swelling|swollen|closing|tight)\b/i',
      '/\bchest (pain|pressure|tightness)\b/i',
      '/\b(unconscious|passed out|fainted|not breathing|unresponsive|seizure|convulsion)\w*\b/i',
      '/\b(not waking up|won\'?t wake( up)?|can\'?t wake (him|her|them|me)( up)?)\b/i',
      '/\b(swallowed|drank|ate) (some |a lot of |too many )?(pills|tablets|medicine|poison|bleach|chemicals|batteries)\b/i',
      '/\b(severe|heavy|uncontrolled|won\'?t stop) bleeding\b/i',
      '/\b(face (is )?drooping|slurred speech|sudden (weakness|numbness|confusion)|stroke)\b/i',
      '/\banaphyla\w*\b.*\b(now|right now|happening|having)\b|\b(having|right now)\b.*\banaphyla\w*/i',
      '/\b(worst headache of my life|sudden severe headache)\b/i',
    ],
  ];

  /**
   * Return 'crisis', 'emergency' or NULL.
   */
  public function detect(string $text): ?string {
    foreach (self::PATTERNS as $type => $patterns) {
      foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text)) {
          return $type;
        }
      }
    }
    return NULL;
  }

  /**
   * Fixed safety message for a detected type.
   */
  public static function message(string $type): string {
    return $type === 'crisis'
      ? 'If you are thinking about harming yourself or may have taken too much of a medicine, get help now: in the United States call or text 988 (Suicide & Crisis Lifeline) or call 911; elsewhere, call your local emergency number. This site cannot provide crisis support.'
      : 'This may be a medical emergency. Call 911 in the United States, or your local emergency number, now. Do not wait for online information. This site provides general education only.';
  }

}
