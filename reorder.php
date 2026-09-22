<?php

/**
 * Plugin Name:       Reorder
 * Plugin URI:        https://github.com/cloak-labs/cloakwp-reorder
 * Description:       Drag rows in the posts list to set menu order. Configured in PHP — no settings UI.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.2
 * Author:            Cloak Labs
 * Author URI:        https://github.com/cloak-labs
 * License:           LGPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/lgpl-3.0.html
 * Text Domain:       reorder
 * Domain Path:       /languages
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
  exit;
}

/*
 * Composer path repos symlink this package outside wp-content. PHP resolves
 * __FILE__/__DIR__ to the real path, which breaks plugins_url()/plugin_basename().
 * Prefer the public mu-plugins path WordPress (and the web server) actually serve.
 */
$cloakwpReorderFile = __FILE__;
$cloakwpReorderDir = __DIR__;
if (defined('WPMU_PLUGIN_DIR')) {
  $cloakwpMuPluginFile = WPMU_PLUGIN_DIR . '/reorder/reorder.php';
  if (is_readable($cloakwpMuPluginFile)) {
    $cloakwpReorderFile = $cloakwpMuPluginFile;
    $cloakwpReorderDir = dirname($cloakwpMuPluginFile);
  }
}

define('CLOAKWP_REORDER_FILE', $cloakwpReorderFile);
define('CLOAKWP_REORDER_DIR', $cloakwpReorderDir);
define('CLOAKWP_REORDER_VERSION', '0.1.0');

if (function_exists('wp_register_plugin_realpath')) {
  wp_register_plugin_realpath(CLOAKWP_REORDER_FILE);
}

if (is_readable(__DIR__ . '/vendor/autoload.php')) {
  require_once __DIR__ . '/vendor/autoload.php';
} elseif (!class_exists(\CloakWP\Reorder\Reorder::class, false)) {
  spl_autoload_register(static function (string $class): void {
    $prefix = 'CloakWP\\Reorder\\';
    if (!str_starts_with($class, $prefix)) {
      return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';

    if (is_readable($path)) {
      require_once $path;
    }
  });
}

use CloakWP\Reorder\Reorder;

/**
 * Deferred default boot: theme/mu-plugin fluent config can call register()
 * before init. If nothing has booted by init priority 1, start inert.
 */
add_action('init', static function (): void {
  if (!Reorder::booted()) {
    Reorder::make()->register();
  }
}, 1);
