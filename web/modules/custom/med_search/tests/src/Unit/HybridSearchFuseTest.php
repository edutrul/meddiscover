<?php

namespace Drupal\Tests\med_search\Unit;

use Drupal\med_search\HybridSearch;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Reciprocal Rank Fusion: scores, ordering and tie-breaks.
 */
#[Group('med_search')]
#[CoversMethod(HybridSearch::class, 'fuse')]
final class HybridSearchFuseTest extends UnitTestCase {

  /**
   * A topic in both lists earns 1/(k+rank) from each and ranks first.
   */
  public function testScoresAreSummedReciprocalRanks(): void {
    $rows = HybridSearch::fuse([
      'lexical' => ['walking', 'knee', 'replacement'],
      'semantic' => ['walking', 'knee'],
    ], 60);

    $this->assertSame(['walking', 'knee', 'replacement'], array_column($rows, 'id'));
    $this->assertEqualsWithDelta(1 / 61 + 1 / 61, $rows[0]['score'], 1e-12);
    $this->assertEqualsWithDelta(1 / 62 + 1 / 62, $rows[1]['score'], 1e-12);
    // Missing from a list earns nothing from it.
    $this->assertEqualsWithDelta(1 / 63, $rows[2]['score'], 1e-12);
    $this->assertSame(['lexical' => 3], $rows[2]['ranks']);
  }

  /**
   * Appearing in both lists beats being first in only one.
   */
  public function testBothListsBeatOneFirstPlace(): void {
    $rows = HybridSearch::fuse([
      'lexical' => ['only-lexical', 'shared'],
      'semantic' => ['only-semantic', 'shared'],
    ], 60);
    $this->assertSame('shared', $rows[0]['id']);
  }

  /**
   * Equal scores: the better single rank wins, then Keyword (first list).
   */
  public function testTieBreaks(): void {
    $rows = HybridSearch::fuse([
      'lexical' => ['a'],
      'semantic' => ['b'],
    ], 60);
    // Same score, same list count, same best rank: the earlier list wins.
    $this->assertSame(['a', 'b'], array_column($rows, 'id'));
  }

  /**
   * A repeated ID inside one list keeps its first rank.
   */
  public function testDuplicatesKeepFirstRank(): void {
    $rows = HybridSearch::fuse(['lexical' => ['x', 'x', 'y']], 60);
    $this->assertSame(['x', 'y'], array_column($rows, 'id'));
    $this->assertSame(['lexical' => 1], $rows[0]['ranks']);
    $this->assertSame(['lexical' => 2], $rows[1]['ranks']);
  }

  /**
   * Empty input gives no rows; a negative k is rejected.
   */
  public function testEdgeCases(): void {
    $this->assertSame([], HybridSearch::fuse(['lexical' => [], 'semantic' => []], 60));
    $this->expectException(\InvalidArgumentException::class);
    HybridSearch::fuse(['lexical' => ['a']], -1);
  }

}
