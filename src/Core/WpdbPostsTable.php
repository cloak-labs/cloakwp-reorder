<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Core;

/**
 * Loads and persists post IDs by menu_order, then post_date, then ID.
 */
final class WpdbPostsTable implements PostsTable
{
  /** @var list<string> */
  public const STATUSES = ['publish', 'pending', 'draft', 'private', 'future'];

  public function orderedIds(string $postType): array
  {
    global $wpdb;

    $statuses = self::STATUSES;
    $statusPlaceholders = implode(',', array_fill(0, count($statuses), '%s'));

    $base = "SELECT ID FROM {$wpdb->posts}
       WHERE post_type = %s
       AND post_status IN ({$statusPlaceholders})
       ORDER BY menu_order ASC, post_date DESC, ID ASC";

    $locked = $wpdb->get_col(
      $wpdb->prepare($base . ' FOR UPDATE', $postType, ...$statuses),
    );

    $ids = is_array($locked) && empty($wpdb->last_error)
      ? $locked
      : $wpdb->get_col($wpdb->prepare($base, $postType, ...$statuses));
    if (!is_array($ids)) {
      return [];
    }

    return array_values(array_map('intval', $ids));
  }

  public function persist(Sequence $sequence): void
  {
    global $wpdb;

    foreach ($sequence->ids() as $menuOrder => $id) {
      $wpdb->update(
        $wpdb->posts,
        ['menu_order' => $menuOrder],
        ['ID' => $id],
        ['%d'],
        ['%d'],
      );

      if (function_exists('clean_post_cache')) {
        clean_post_cache($id);
      }
    }
  }

  public function begin(): void
  {
    global $wpdb;
    $wpdb->query('START TRANSACTION');
  }

  public function commit(): void
  {
    global $wpdb;
    $wpdb->query('COMMIT');
  }

  public function rollback(): void
  {
    global $wpdb;
    $wpdb->query('ROLLBACK');
  }
}
