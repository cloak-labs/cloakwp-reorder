<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Core;

interface PostsTable
{
  /**
   * @return list<int>
   */
  public function orderedIds(string $postType): array;

  public function persist(Sequence $sequence): void;

  public function begin(): void;

  public function commit(): void;

  public function rollback(): void;
}
