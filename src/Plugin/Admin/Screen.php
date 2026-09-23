<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Plugin\Admin;

use CloakWP\Reorder\Core\Config;
use WP_Screen;

/**
 * Whether the current posts list table can accept a reorder.
 */
final class Screen
{
  public function __construct(
    private readonly Config $config,
  ) {
  }

  public function postType(?WP_Screen $screen = null): ?string
  {
    $screen ??= $this->currentScreen();
    if ($screen === null || $screen->base !== 'edit') {
      return null;
    }

    $postType = (string) ($screen->post_type ?: 'post');

    return $postType !== '' ? $postType : null;
  }

  public function isEnabled(?WP_Screen $screen = null): bool
  {
    $postType = $this->postType($screen);

    return $postType !== null && $this->config->enables($postType);
  }

  public function isReorderable(?WP_Screen $screen = null): bool
  {
    if (!$this->isEnabled($screen)) {
      return false;
    }

    $get = $this->queryVars();

    if ($this->filled($get['s'] ?? null)) {
      return false;
    }

    if ($this->filled($get['author'] ?? null)) {
      return false;
    }

    $status = (string) ($get['post_status'] ?? 'all');
    if ($status !== '' && $status !== 'all') {
      return false;
    }

    $month = (string) ($get['m'] ?? '0');
    if ($month !== '' && $month !== '0') {
      return false;
    }

    $orderby = (string) ($get['orderby'] ?? '');
    if ($orderby !== '' && $orderby !== 'menu_order') {
      return false;
    }

    if ($this->filled($get['cat'] ?? null)) {
      return false;
    }

    $postType = $this->postType($screen);
    if ($postType !== null && function_exists('get_object_taxonomies')) {
      foreach (get_object_taxonomies($postType) as $taxonomy) {
        if ($this->filled($get[$taxonomy] ?? null)) {
          return false;
        }
      }
    }

    return true;
  }

  public function paged(): int
  {
    return max(1, (int) ($this->queryVars()['paged'] ?? 1));
  }

  public function perPage(string $postType): int
  {
    $option = 'edit_' . $postType . '_per_page';
    $perPage = (int) get_user_option($option);
    if ($perPage < 1) {
      $perPage = 20;
    }

    // Same filters as wp_edit_posts_query(), so the saved page offset matches the list.
    $perPage = (int) apply_filters("edit_{$postType}_per_page", $perPage);
    $perPage = (int) apply_filters('edit_posts_per_page', $perPage, $postType);

    return $perPage > 0 ? $perPage : 20;
  }

  private function currentScreen(): ?WP_Screen
  {
    if (!function_exists('get_current_screen')) {
      return null;
    }

    $screen = get_current_screen();

    return $screen instanceof WP_Screen ? $screen : null;
  }

  /**
   * @return array<string, mixed>
   */
  private function queryVars(): array
  {
    return is_array($_GET) ? $_GET : [];
  }

  private function filled(mixed $value): bool
  {
    if ($value === null || $value === false) {
      return false;
    }

    if (is_array($value)) {
      return $value !== [];
    }

    return trim((string) $value) !== '';
  }
}
