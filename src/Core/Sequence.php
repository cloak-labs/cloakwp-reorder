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

  /**
   * Rewrite the relative order of $visibleIds. Other IDs stay put.
   * The list on screen is not always a contiguous slice of the stored sequence
   * (hierarchy, pagination, or a sort tie), so requiring that slice rejects every move.
   *
   * @param list<int> $visibleIds
   */
  public function reorderVisible(array $visibleIds): self
  {
    $wanted = array_values(array_map(intval(...), $visibleIds));
    if ($wanted === []) {
      throw new InvalidArgumentException('A reorder page cannot be empty.');
    }

    $pending = array_fill_keys($wanted, true);
    if (count($pending) !== count($wanted)) {
      throw new InvalidArgumentException('Sequence IDs must be unique.');
    }

    $slots = [];
    foreach ($this->ids as $index => $id) {
      if (!isset($pending[$id])) {
        continue;
      }
      $slots[] = $index;
      unset($pending[$id]);
    }

    if ($pending !== []) {
      throw StalePageException::mismatch();
    }

    $next = $this->ids;
    foreach ($slots as $position => $index) {
      $next[$index] = $wanted[$position];
    }

    return new self($next);
  }
}
