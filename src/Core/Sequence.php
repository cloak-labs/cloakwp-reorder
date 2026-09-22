<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Core;

use InvalidArgumentException;

/**
 * Ordered list of object IDs. Entity-neutral so taxonomy terms can reuse it later.
 */
final class Sequence
{
  /** @var list<int> */
  private readonly array $ids;

  /**
   * @param list<int> $ids
   */
  public function __construct(array $ids)
  {
    $normalized = [];

    foreach ($ids as $id) {
      $id = (int) $id;
      if ($id < 1) {
        throw new InvalidArgumentException('Sequence IDs must be positive integers.');
      }
      if (isset($normalized[$id])) {
        throw new InvalidArgumentException('Sequence IDs must be unique.');
      }
      $normalized[$id] = $id;
    }

    $this->ids = array_values($normalized);
  }

  /**
   * @return list<int>
   */
  public function ids(): array
  {
    return $this->ids;
  }

  public function count(): int
  {
    return count($this->ids);
  }

  /**
   * Replace the page window at $offset with $pageIds (same set, new order).
   *
   * @param list<int> $pageIds
   */
  public function splicePage(array $pageIds, int $offset): self
  {
    if ($offset < 0) {
      throw new InvalidArgumentException('Page offset cannot be negative.');
    }

    $pageIds = array_values(array_map('intval', $pageIds));
    if ($pageIds === []) {
      throw new InvalidArgumentException('A reorder page cannot be empty.');
    }

    $count = count($pageIds);
    if ($offset + $count > count($this->ids)) {
      throw StalePageException::mismatch();
    }

    $current = array_slice($this->ids, $offset, $count);
    $expected = $current;
    $submitted = $pageIds;
    sort($expected);
    sort($submitted);

    if ($expected !== $submitted) {
      throw StalePageException::mismatch();
    }

    $before = array_slice($this->ids, 0, $offset);
    $after = array_slice($this->ids, $offset + $count);

    return new self([...$before, ...$pageIds, ...$after]);
  }
}
