<?php

declare(strict_types=1);

use CloakWP\Reorder\Tests\WpStubs;

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_readable($autoload)) {
  require $autoload;
} else {
  spl_autoload_register(static function (string $class): void {
    $prefix = 'CloakWP\\Reorder\\';
    if (!str_starts_with($class, $prefix)) {
      return;
    }
    $relative = substr($class, strlen($prefix));
    $path = dirname(__DIR__) . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_readable($path)) {
      require_once $path;
    }
  });
}

require_once __DIR__ . '/WpStubs.php';

if (!class_exists('WP_Screen')) {
  class WP_Screen
  {
    public string $base = '';
    public string $post_type = '';
  }
}

if (!class_exists('WP_Query')) {
  class WP_Query
  {
    /** @var array<string, mixed> */
    public array $vars = [];

    public function is_main_query(): bool
    {
      return true;
    }

    public function set(string $key, mixed $value): void
    {
      $this->vars[$key] = $value;
    }
  }
}

if (!class_exists('WP_REST_Request')) {
  class WP_REST_Request
  {
    /** @param array<string, mixed> $params */
    public function __construct(private array $params = [])
    {
    }

    public function get_param(string $key): mixed
    {
      return $this->params[$key] ?? null;
    }
  }
}

if (!class_exists('WP_REST_Response')) {
  class WP_REST_Response
  {
    public function __construct(public mixed $data = null, public int $status = 200)
    {
    }
  }
}

if (!class_exists('WP_Error')) {
  class WP_Error
  {
    public function __construct(
      public string $code = '',
      public string $message = '',
      public mixed $data = [],
    ) {
    }

    public function get_error_message(): string
    {
      return $this->message;
    }

    public function get_error_data(): mixed
    {
      return $this->data;
    }
  }
}

if (!function_exists('sanitize_key')) {
  function sanitize_key($key): string
  {
    $key = strtolower((string) $key);

    return (string) preg_replace('/[^a-z0-9_\-]/', '', $key);
  }
}

if (!function_exists('get_post_type_object')) {
  function get_post_type_object($slug)
  {
    return WpStubs::$postTypeObjects[(string) $slug] ?? null;
  }
}

if (!function_exists('get_post_types')) {
  function get_post_types($args = [], $output = 'names')
  {
    $names = [];
    foreach (WpStubs::$postTypeObjects as $slug => $object) {
      if (!empty($args['show_ui']) && empty($object->show_ui)) {
        continue;
      }
      $names[] = $slug;
    }

    return $names;
  }
}

if (!function_exists('is_post_type_hierarchical')) {
  function is_post_type_hierarchical($postType): bool
  {
    $object = get_post_type_object($postType);

    return (bool) ($object->hierarchical ?? false);
  }
}

if (!function_exists('get_object_taxonomies')) {
  function get_object_taxonomies($postType)
  {
    return WpStubs::$taxonomies[(string) $postType] ?? [];
  }
}

if (!function_exists('current_user_can')) {
  function current_user_can($cap, $id = null): bool
  {
    $key = $id !== null ? $cap . ':' . $id : (string) $cap;

    return WpStubs::$caps[$key] ?? false;
  }
}

if (!function_exists('add_action')) {
  function add_action($hook, $callback, $priority = 10, $accepted_args = 1): void
  {
    WpStubs::$actions[] = [
      'hook' => $hook,
      'callback' => $callback,
      'priority' => $priority,
    ];
  }
}

if (!function_exists('add_filter')) {
  function add_filter($hook, $callback, $priority = 10, $accepted_args = 1): void
  {
    WpStubs::$filters[] = [
      'hook' => $hook,
      'callback' => $callback,
      'priority' => $priority,
    ];
  }
}

if (!function_exists('apply_filters')) {
  function apply_filters($hook, $value, ...$args)
  {
    return WpStubs::$filterValues[$hook] ?? $value;
  }
}

if (!function_exists('do_action')) {
  function do_action($hook, ...$args): void
  {
    WpStubs::$didActions[] = [
      'hook' => $hook,
      'args' => $args,
    ];
  }
}

if (!function_exists('__')) {
  function __(string $text, string $domain = 'default'): string
  {
    return $text;
  }
}

if (!function_exists('esc_html__')) {
  function esc_html__(string $text, string $domain = 'default'): string
  {
    return $text;
  }
}

if (!function_exists('esc_html')) {
  function esc_html($text): string
  {
    return htmlspecialchars((string) $text, ENT_QUOTES);
  }
}

if (!function_exists('esc_attr')) {
  function esc_attr($text): string
  {
    return htmlspecialchars((string) $text, ENT_QUOTES);
  }
}

if (!function_exists('esc_attr__')) {
  function esc_attr__(string $text, string $domain = 'default'): string
  {
    return $text;
  }
}

if (!function_exists('esc_url_raw')) {
  function esc_url_raw($url): string
  {
    return (string) $url;
  }
}

if (!function_exists('rest_url')) {
  function rest_url($path = ''): string
  {
    return 'https://example.test/wp-json/' . ltrim((string) $path, '/');
  }
}

if (!function_exists('wp_create_nonce')) {
  function wp_create_nonce($action): string
  {
    return 'nonce-' . (string) $action;
  }
}

if (!function_exists('plugins_url')) {
  function plugins_url($path = '', $plugin = ''): string
  {
    return rtrim(WpStubs::$pluginsUrlBase, '/');
  }
}

if (!function_exists('trailingslashit')) {
  function trailingslashit($value): string
  {
    return rtrim((string) $value, '/\\') . '/';
  }
}

if (!function_exists('wp_register_script')) {
  function wp_register_script($handle, $src, $deps = [], $ver = false, $args = true): void
  {
    WpStubs::$registeredScripts[$handle] = [
      'src' => (string) $src,
      'deps' => array_values((array) $deps),
      'ver' => (string) $ver,
    ];
  }
}

if (!function_exists('wp_register_style')) {
  function wp_register_style($handle, $src, $deps = [], $ver = false): void
  {
    WpStubs::$registeredStyles[$handle] = [
      'src' => (string) $src,
      'deps' => array_values((array) $deps),
      'ver' => (string) $ver,
    ];
  }
}

if (!function_exists('wp_script_is')) {
  function wp_script_is($handle, $list = 'enqueued'): bool
  {
    if ($list === 'registered') {
      return isset(WpStubs::$registeredScripts[$handle]);
    }

    return in_array($handle, WpStubs::$enqueuedScripts, true);
  }
}

if (!function_exists('wp_enqueue_script')) {
  function wp_enqueue_script($handle): void
  {
    WpStubs::$enqueuedScripts[] = $handle;
  }
}

if (!function_exists('wp_enqueue_style')) {
  function wp_enqueue_style($handle): void
  {
    WpStubs::$enqueuedStyles[] = $handle;
  }
}

if (!function_exists('wp_add_inline_script')) {
  function wp_add_inline_script($handle, $data, $position = 'after'): void
  {
    WpStubs::$inlineScripts[$handle] = (string) $data;
  }
}

if (!function_exists('wp_json_encode')) {
  function wp_json_encode($data, $options = 0, $depth = 512): string|false
  {
    return json_encode($data, $options);
  }
}

if (!function_exists('get_current_screen')) {
  function get_current_screen()
  {
    return WpStubs::$currentScreen;
  }
}

if (!function_exists('is_admin')) {
  function is_admin(): bool
  {
    return WpStubs::$isAdmin;
  }
}

if (!function_exists('get_user_option')) {
  function get_user_option($option)
  {
    return WpStubs::$userOptions[(string) $option] ?? false;
  }
}

if (!function_exists('register_rest_route')) {
  function register_rest_route($namespace, $route, $args = []): void
  {
    WpStubs::$restRoutes[] = [
      'route' => trim((string) $namespace, '/') . '/' . ltrim((string) $route, '/'),
      'args' => $args,
    ];
  }
}

if (!function_exists('clean_post_cache')) {
  function clean_post_cache($id): void
  {
    WpStubs::$cleanedPostIds[] = (int) $id;
  }
}
