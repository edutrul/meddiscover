<?php

namespace Drupal\Tests\med_search\Unit;

use Drupal\med_search\HomepageRouter;
use Drupal\med_search\UrgencyDetector;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * The homepage decision: urgent, Ask (RAG) or Hybrid.
 */
#[Group('med_search')]
#[CoversClass(HomepageRouter::class)]
final class HomepageRouterTest extends UnitTestCase {

  /**
   * The router under test, with the real (dependency-free) urgency rules.
   */
  private HomepageRouter $router;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->router = new HomepageRouter(new UrgencyDetector());
    $this->router->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Inputs and the route the homepage takes.
   */
  public static function routes(): array {
    return [
      'question mark' => ['What is HbA1c?', HomepageRouter::MED_ASK],
      'question word, no mark' => ['what is hba1c', HomepageRouter::MED_ASK],
      'question opener' => ['is the A1C test done fasting', HomepageRouter::MED_ASK],
      'how to' => ['how to lower a1c', HomepageRouter::MED_ASK],
      'topic phrase' => ['knee pain', HomepageRouter::MED_HYBRID],
      'title' => ['Walking Problems', HomepageRouter::MED_HYBRID],
      'urgent beats question' => ['Is chest pain right now an emergency?', HomepageRouter::MED_URGENT],
      'crisis' => ['I want to die', HomepageRouter::MED_URGENT],
      // Known gaps of a word pattern, kept visible on purpose.
      'gap: contraction' => ["what's hba1c", HomepageRouter::MED_HYBRID],
      'gap: request' => ['explain HbA1c', HomepageRouter::MED_HYBRID],
    ];
  }

  /**
   * Each input takes the documented route.
   */
  #[DataProvider('routes')]
  public function testRoute(string $query, string $expected): void {
    $this->assertSame($expected, $this->router->route($query));
  }

  /**
   * The explanation names the part of the rule that matched.
   */
  public function testQuestionReason(): void {
    $this->assertSame('ends with a question mark', (string) $this->router->questionReason('What is HbA1c?'));
    $this->assertSame('starts with the question word “what”', (string) $this->router->questionReason('What is HbA1c'));
    $this->assertSame('starts with “is the”, a question opener', (string) $this->router->questionReason('Is the test done fasting'));
    $this->assertNull($this->router->questionReason('knee pain'));
  }

  /**
   * The urgent group is reported for the safety message.
   */
  public function testUrgency(): void {
    $this->assertSame('emergency', $this->router->urgency('I have chest pain right now'));
    $this->assertNull($this->router->urgency('knee pain'));
  }

}
