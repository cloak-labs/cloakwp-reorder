<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Plugin\Admin;

use CloakWP\Reorder\Core\Config;
use CloakWP\Reorder\Plugin\Assets;
use WP_Query;
use WP_Screen;

final class ListTable
{
  public function __construct(
    private readonly Config $config,
    private readonly Screen $screen,
    private readonly Assets $assets,
  ) {
  }

  public function register(): void
  {
    add_action('current_screen', [$this, 'bind']);
    add_action('pre_get_posts', [$this, 'orderQuery']);
    add_filter('hidden_columns', [$this, 'keepHandleVisible'], 10, 2);
  }

  public function bind(mixed $screen): void
  {
    if (!$screen instanceof WP_Screen) {
      return;
    }

    if (!$this->screen->isEnabled($screen)) {
      return;
    }

    $postType = $this->screen->postType($screen);
    if ($postType === null) {
      return;
    }

    add_filter("manage_{$postType}_posts_columns", [$this, 'columns']);
    add_action("manage_{$postType}_posts_custom_column", [$this, 'renderColumn'], 10, 2);
    add_action('admin_enqueue_scripts', [$this, 'enqueue']);
  }

  /**
   * @param array<string, string> $columns
   * @return array<string, string>
   */
  public function columns(array $columns): array
  {
    if (!$this->screen->isReorderable()) {
      return $columns;
    }

    $handle = [
      'reorder' => sprintf(
        '<span class="screen-reader-text">%s</span>',
        esc_html__('Order', 'reorder'),
      ),
    ];

    if (isset($columns['cb'])) {
      return ['cb' => $columns['cb'], ...$handle, ...array_diff_key($columns, ['cb' => true])];
    }

    return [...$handle, ...$columns];
  }

  public function renderColumn(string $column, int $postId): void
  {
    if ($column !== 'reorder' || !$this->screen->isReorderable()) {
      return;
    }

    printf(
      '<button type="button" class="reorder-handle" aria-label="%s" data-id="%d"><span class="dashicons dashicons-menu" aria-hidden="true"></span></button>',
      esc_attr__('Move', 'reorder'),
      $postId,
    );
  }

  public function orderQuery(WP_Query $query): void
  {
    if (!is_admin() || !$query->is_main_query() || !$this->screen->isReorderable()) {
      return;
    }

    $query->set('orderby', [
      'menu_order' => 'ASC',
      'date' => 'DESC',
    ]);
  }

  public function enqueue(string $hookSuffix): void
  {
    if ($hookSuffix !== 'edit.php' || !$this->screen->isReorderable()) {
      return;
    }

    $postType = $this->screen->postType();
    if ($postType === null) {
      return;
    }

    $this->assets->enqueue($postType, $this->screen->paged(), $this->screen->perPage($postType));
  }

  /**
   * @param array<int, string> $hidden
   * @return array<int, string>
   */
  public function keepHandleVisible(array $hidden, mixed $screen): array
  {
    if (!$screen instanceof WP_Screen || !$this->screen->isEnabled($screen)) {
      return $hidden;
    }

    return array_values(array_filter(
      $hidden,
      static fn(string $column): bool => $column !== 'reorder',
    ));
  }
}
