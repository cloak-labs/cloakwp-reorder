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
    // Page number and page size stay on the request for the list screen.
    // The save reorders the submitted IDs wherever they sit in the sequence.
    if ($paged < 1 || $perPage < 1) {
      throw new InvalidArgumentException('Page coordinates must be positive.');
    }

    $this->table->begin();

    try {
      $next = (new Sequence($this->table->orderedIds($postType)))
        ->reorderVisible($pageIds);
      $this->table->persist($next);
      $this->table->commit();

      return $next;
    } catch (Throwable $e) {
      $this->table->rollback();
      throw $e;
    }
  }
}
