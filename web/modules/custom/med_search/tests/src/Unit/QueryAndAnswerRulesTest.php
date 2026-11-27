<?php

namespace Drupal\Tests\med_search\Unit;

use Drupal\med_search\ConversationalSearch;
use Drupal\med_search\QueryText;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Query validation and the answer checks that need no database.
 */
#[Group('med_search')]
#[CoversClass(QueryText::class)]
#[CoversClass(ConversationalSearch::class)]
final class QueryAndAnswerRulesTest extends UnitTestCase {

  /**
   * Queries are trimmed and bounded; limits are bounded.
   */
  public function testQueryValidation(): void {
    $this->assertSame('knee pain', QueryText::validate('  knee pain ', 5));
    foreach ([['   ', 5], [str_repeat('a', 501), 5], ['ok', 0], ['ok', 21]] as [$query, $limit]) {
      try {
        QueryText::validate($query, $limit);
        $this->fail('Invalid input must be rejected.');
      }
      catch (\InvalidArgumentException) {
        $this->addToAssertionCount(1);
      }
    }
  }

  /**
   * Names compare without case or punctuation.
   */
  public function testNormalizeName(): void {
    $this->assertSame(QueryText::normalizeName('Hemoglobin A1c'), QueryText::normalizeName('hemoglobin-a1c!'));
  }

  /**
   * Malformed output, a fabricated citation or unsafe text is withheld.
   */
  public function testVerifyRejectsBeforeReadingSources(): void {
    $sources = ['S1' => ['id' => '1', 'node' => NULL]];
    $this->assertSame(['status' => 'withheld', 'reason' => 'invalid'], ConversationalSearch::verify(['statements' => []], $sources, 100));
    $declined = ConversationalSearch::verify(['answerable' => FALSE, 'statements' => []], $sources, 100);
    $this->assertSame(['status' => 'abstained', 'reason' => 'model_declined'], $declined);
    // "S9" was never provided: a fabricated citation.
    $this->assertSame(['status' => 'withheld', 'reason' => 'invalid'], ConversationalSearch::verify([
      'answerable' => TRUE,
      'statements' => [['text' => 'HbA1c measures average blood glucose.', 'sources' => ['S9']]],
    ], $sources, 100));
    // Doses are never shown.
    $this->assertSame(['status' => 'withheld', 'reason' => 'unsafe'], ConversationalSearch::verify([
      'answerable' => TRUE,
      'statements' => [['text' => 'Take 500 mg of metformin daily.', 'sources' => ['S1']]],
    ], $sources, 100));
  }

  /**
   * Support is the share of content words found in the sources.
   */
  public function testSupport(): void {
    $sources = 'The HbA1c blood test measures average glucose over three months.';
    $this->assertSame(1.0, ConversationalSearch::support('HbA1c measures average glucose.', $sources));
    $this->assertLessThan(ConversationalSearch::MED_MIN_SUPPORT, ConversationalSearch::support('Vitamin injections cure arthritis quickly.', $sources));
  }

  /**
   * The schema only allows citations to the provided labels.
   */
  public function testSchemaLimitsCitations(): void {
    $schema = ConversationalSearch::schema(['S1', 'S2']);
    $this->assertSame(['S1', 'S2'], $schema['properties']['statements']['items']['properties']['sources']['items']['enum']);
  }

}
