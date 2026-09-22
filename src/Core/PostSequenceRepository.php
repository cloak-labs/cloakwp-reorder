<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Core;

use Throwable;

final class PostSequenceRepository
{
  public function __construct(
    private readonly PostsTable $table = new WpdbPostsTable(),
  ) {
  }

  /**
   * @param list<int> $pageIds
   */
  public function applyPage(string $postType, array $pageIds, int $paged, int $perPage): Sequence
  {
    $offset = max(0, ($paged - 1) * max(1, $perPage));

    $this->table->begin();

    try {
      $next = (new Sequence($this->table->orderedIds($postType)))
        ->splicePage($pageIds, $offset);
      $this->table->persist($next);
      $this->table->commit();

      return $next;
    } catch (Throwable $e) {
      $this->table->rollback();
      throw $e;
    }
  }
}
