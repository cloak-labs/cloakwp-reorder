<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Tests;

use CloakWP\Reorder\Core\PostsTable;
use CloakWP\Reorder\Core\Sequence;

final class FakePostsTable implements PostsTable
{
  /** @var list<int> */
  public array $ids;

  public int $begins = 0;
  public int $commits = 0;
  public int $rollbacks = 0;
  /** @var list<list<int>> */
  public array $persisted = [];

  /**
   * @param list<int> $ids
   */
  public function __construct(array $ids)
  {
    $this->ids = $ids;
  }

  public function orderedIds(string $postType): array
  {
    return $this->ids;
  }

  public function persist(Sequence $sequence): void
  {
    $this->ids = $sequence->ids();
    $this->persisted[] = $sequence->ids();
  }

  public function begin(): void
  {
    $this->begins++;
  }

  public function commit(): void
  {
    $this->commits++;
  }

  public function rollback(): void
  {
    $this->rollbacks++;
  }
}
