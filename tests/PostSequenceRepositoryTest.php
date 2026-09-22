<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Tests;

use CloakWP\Reorder\Core\PostSequenceRepository;
use CloakWP\Reorder\Core\StalePageException;
use PHPUnit\Framework\TestCase;

final class PostSequenceRepositoryTest extends TestCase
{
  public function testAppliesAPageInsideATransaction(): void
  {
    $table = new FakePostsTable([1, 2, 3, 4, 5, 6]);
    $repository = new PostSequenceRepository($table);

    $next = $repository->applyPage('project', [6, 4, 5], 2, 3);

    $this->assertSame([1, 2, 3, 6, 4, 5], $next->ids());
    $this->assertSame(1, $table->begins);
    $this->assertSame(1, $table->commits);
    $this->assertSame(0, $table->rollbacks);
    $this->assertSame([[1, 2, 3, 6, 4, 5]], $table->persisted);
  }

  public function testRollsBackStaleSubmissions(): void
  {
    $table = new FakePostsTable([1, 2, 3, 4]);
    $repository = new PostSequenceRepository($table);

    try {
      $repository->applyPage('project', [9, 4], 2, 2);
      $this->fail('Expected a stale page exception.');
    } catch (StalePageException) {
      $this->assertSame(1, $table->begins);
      $this->assertSame(0, $table->commits);
      $this->assertSame(1, $table->rollbacks);
      $this->assertSame([], $table->persisted);
    }
  }
}
