<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Plugin\Admin;

use CloakWP\Reorder\Core\Config;
use CloakWP\Reorder\Plugin\Assets;
use WP_Query;
use WP_Screen;

final class ListTable
{
  /**
   * Quick Edit rebuilds one row over admin-ajax. That request never fires
   * current_screen, so the column hooks registered in bind() are absent and
   * the new row is one cell short. The title then sits in the 20px handle
   * column. This flag is set only when the list that opened Quick Edit was
   * reorderable.
   */
  private bool $inlineSaveHandle = false;

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
    add_action('quick_edit_custom_box', [$this, 'quickEditMarker'], 10, 2);
    add_action('wp_ajax_inline-save', [$this, 'prepareInlineSave'], 0);
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

    $this->listenForColumns($postType);
    add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    add_action('load-edit.php', [$this, 'flattenHierarchicalQuery']);
  }

  /**
   * Hierarchical edit screens otherwise render a page tree. That tree is not
   * the flat menu_order page the save request compares against, so a drop is
   * rejected and the row snaps back. Setting orderby before wp_edit_posts_query()
   * keeps the list flat. An explicit column sort is left alone.
   */
  public function flattenHierarchicalQuery(): void
  {
    if (!$this->screen->isReorderable()) {
      return;
    }

    $postType = $this->screen->postType();
    if ($postType === null || !is_post_type_hierarchical($postType)) {
      return;
    }

    $orderby = $_GET['orderby'] ?? null;
    if (is_string($orderby) && $orderby !== '') {
      return;
    }

    $_GET['orderby'] = 'menu_order';
    $_REQUEST['orderby'] = 'menu_order';
  }

  public function quickEditMarker(string $column, string $postType): void
  {
    if ($column !== 'reorder' || !$this->config->enables($postType) || !$this->screen->isReorderable()) {
      return;
    }

    echo '<input type="hidden" name="reorder_active" value="1" />';
  }

  public function prepareInlineSave(): void
  {
    $postType = $_POST['post_type'] ?? '';
    $marker = $_POST['reorder_active'] ?? '';
    if (!is_string($postType) || !is_string($marker) || $marker !== '1') {
      return;
    }

    $postType = sanitize_key($postType);
    if ($postType === '' || !$this->config->enables($postType)) {
      return;
    }

    $this->inlineSaveHandle = true;
    $this->listenForColumns($postType);
  }

  /**
   * @param array<string, string> $columns
   * @return array<string, string>
   */
  public function columns(array $columns): array
  {
    if (isset($columns['reorder']) || !$this->showsHandle()) {
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
    if ($column !== 'reorder' || !$this->showsHandle()) {
      return;
    }

    printf(
      '<span class="reorder-handle" role="button" tabindex="0" aria-label="%s" data-id="%d"><span class="dashicons dashicons-menu" aria-hidden="true"></span></span>',
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

  private function showsHandle(): bool
  {
    return $this->inlineSaveHandle || $this->screen->isReorderable();
  }

  private function listenForColumns(string $postType): void
  {
    add_filter("manage_{$postType}_posts_columns", [$this, 'columns']);
    add_action("manage_{$postType}_posts_custom_column", [$this, 'renderColumn'], 10, 2);
  }
}
