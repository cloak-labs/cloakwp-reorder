<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Tests;

use CloakWP\Reorder\Core\Sequence;
use CloakWP\Reorder\Core\StalePageException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SequenceTest extends TestCase
{
  public function testSplicesTheCurrentPageAndLeavesNeighbors(): void
  {
    $sequence = new Sequence([1, 2, 3, 4, 5, 6]);

    $next = $sequence->splicePage([6, 4, 5], 3);

    $this->assertSame([1, 2, 3, 6, 4, 5], $next->ids());
    $this->assertSame([1, 2, 3, 4, 5, 6], $sequence->ids());
  }

  public function testRejectsAStalePageThatDoesNotMatchTheWindow(): void
  {
    $sequence = new Sequence([1, 2, 3, 4, 5]);

    $this->expectException(StalePageException::class);
    $sequence->splicePage([9, 4], 3);
  }

  public function testRejectsAnOffsetPastTheEnd(): void
  {
    $sequence = new Sequence([1, 2, 3]);

    $this->expectException(StalePageException::class);
    $sequence->splicePage([1, 2], 2);
  }

  public function testRejectsDuplicateAndNonPositiveIds(): void
  {
    $this->expectException(InvalidArgumentException::class);
    new Sequence([1, 1]);
  }
}
