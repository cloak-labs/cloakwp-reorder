<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Plugin;

/**
 * Registers and enqueues list-table assets via plugin_dir_url().
 */
final class Assets
{
  public const SCRIPT_HANDLE = 'reorder-edit-list';
  public const STYLE_HANDLE = 'reorder-edit-list';

  public function __construct(
    private readonly string $pluginFile,
  ) {
  }

  public function enqueue(string $postType, int $paged, int $perPage): void
  {
    $this->registerHandles();

    if (!wp_script_is(self::SCRIPT_HANDLE, 'registered')) {
      return;
    }

    wp_enqueue_script(self::SCRIPT_HANDLE);
    wp_enqueue_style(self::STYLE_HANDLE);

    wp_add_inline_script(
      self::SCRIPT_HANDLE,
      'window.reorderList = ' . wp_json_encode([
        'restUrl' => esc_url_raw(rest_url('reorder/v1/posts')),
        'nonce' => wp_create_nonce('wp_rest'),
        'postType' => $postType,
        'paged' => $paged,
        'perPage' => $perPage,
        'i18n' => [
          'saved' => __('Order saved.', 'reorder'),
          'error' => __('Could not save the order. The list was restored.', 'reorder'),
        ],
      ], JSON_UNESCAPED_SLASHES) . ';',
      'before',
    );
  }

  public function registerHandles(): void
  {
    $version = defined('CLOAKWP_REORDER_VERSION')
      ? CLOAKWP_REORDER_VERSION
      : '0.1.0';

    $jsPath = $this->path('resources/js/edit-list.js');
    $cssPath = $this->path('resources/css/edit-list.css');

    if (is_readable($jsPath)) {
      $jsUrl = $this->url('resources/js/edit-list.js');
      $jsVersion = $version . '.' . (string) filemtime($jsPath);
      wp_register_script(
        self::SCRIPT_HANDLE,
        $jsUrl,
        ['jquery', 'jquery-ui-sortable', 'wp-a11y', 'wp-api-fetch'],
        $jsVersion,
        true,
      );
    }

    if (is_readable($cssPath)) {
      $cssUrl = $this->url('resources/css/edit-list.css');
      $cssVersion = $version . '.' . (string) filemtime($cssPath);
      wp_register_style(self::STYLE_HANDLE, $cssUrl, ['dashicons'], $cssVersion);
    }
  }

  public function url(string $relative): string
  {
    $relative = ltrim($relative, '/');
    $base = plugins_url('', $this->pluginFile);

    if (defined('WPMU_PLUGIN_URL') && (str_contains($base, '/var/www/') || str_contains($base, '/plugins/var/'))) {
      $base = trailingslashit(WPMU_PLUGIN_URL) . 'reorder';
    }

    return trailingslashit($base) . $relative;
  }

  public function path(string $relative): string
  {
    $dir = defined('CLOAKWP_REORDER_DIR')
      ? CLOAKWP_REORDER_DIR
      : dirname($this->pluginFile);

    $real = realpath($dir);
    if ($real !== false) {
      $dir = $real;
    }

    return rtrim($dir, '/\\') . '/' . ltrim($relative, '/');
  }
}
