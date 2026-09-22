# Reorder

Drag rows in the WordPress posts list (`edit.php`) to set `menu_order`. Each move saves immediately — nothing to submit. Configured in PHP; no settings page, no admin notices, no frontend query rewriting.

This does **not** change `WP_Query` on the public site. Queries that should follow the custom order must request it:

```php
get_posts([
  'orderby' => [
    'menu_order' => 'ASC',
    'date' => 'DESC',
  ],
]);
```

## Install paths

### 1. Composer (must-use plugin) — recommended

```bash
composer require cloakwp/reorder
```

Package type is `wordpress-muplugin`. With [`composer/installers`](https://github.com/composer/installers) configured, that installs to:

```
wp-content/mu-plugins/reorder/
```

(Your project may map that path differently — e.g. Bedrock uses `public/app/mu-plugins/`.)

**Important:** WordPress core only auto-loads PHP files directly in `mu-plugins/`. It does **not** load plugins nested in subdirectories like `mu-plugins/reorder/reorder.php`. You need an autoloader (or a tiny stub) for subdirectory must-use plugins.

**Recommended:** [Roots Bedrock Autoloader](https://github.com/roots/bedrock-autoloader) — it scans `mu-plugins/*/*.php` for plugin headers and includes them. Ships with [Bedrock](https://roots.io/bedrock/); usable in any WordPress project as `roots/bedrock-autoloader`. Once loaded, this package shows under **Plugins → Must-Use** (not the toggleable Plugins list).

**Without an autoloader**, add a one-line stub at the mu-plugins root:

```php
<?php
// wp-content/mu-plugins/reorder-loader.php
require WPMU_PLUGIN_DIR . '/reorder/reorder.php';
```

Until something calls `register()` with post types (or `allPostTypes()`), the plugin is inert.

```php
use CloakWP\Reorder\Reorder;

Reorder::make()
  ->postTypes(['project', 'service'])
  ->register();

// or every eligible type:
Reorder::make()
  ->allPostTypes()
  ->register();
```

Call this from a theme `functions.php` or mu-plugin **before** `init` (priority 1). If you never call `register()`, the bootstrap starts with no post types enabled.

### 2. Traditional plugin install (download as a zip)

For sites that don’t use Composer — install it like any other WordPress plugin:

1. Open the [GitHub repository page](https://github.com/cloak-labs/cloakwp-reorder).
2. Click the green **Code** button, then **Download ZIP**.
3. Unzip the file. You’ll get a folder named something like `cloakwp-reorder-main`.
4. Rename that folder to `reorder` (optional but keeps the Plugins list tidy).
5. Install it in either way:
   - **WordPress admin:** Plugins → Add New → Upload Plugin → choose the zip (re-zip the renamed folder if you renamed it) → Install Now → Activate, **or**
   - **Manually:** upload the `reorder` folder into `wp-content/plugins/` on your server (via FTP/SFTP or your host’s file manager), then go to Plugins and click **Activate**.

Same inert defaults as the Composer path. Developers still enable types via fluent `register()` or the config filter.

## Fluent API

```php
use CloakWP\Reorder\Reorder;

Reorder::make()
  ->postTypes(['team', 'portfolio', 'faq'])
  ->register();
```

| Method | Effect |
|--------|--------|
| `postTypes(['project'])` | Allowlist. Only those slugs, and only if they are eligible. |
| `allPostTypes()` | Every eligible post type (resolved when the screen/REST request runs). |

### Eligibility

A type can be reordered when it is:

- registered with `show_ui`
- **not** `page` (core pages keep their tree UI)
- **not** `attachment` or other internals (`revision`, `wp_block`, ACF UI types, …)

Custom post types may be hierarchical in WordPress; this plugin still only writes `menu_order`, never `post_parent`.

### Config filter

```php
add_filter('cloakwp/reorder/config', function ($config) {
  return $config->withPostTypes(['team', 'service']);
});
```

### Completed reorder

```php
add_action('cloakwp/reorder/updated', function (string $postType, array $ids): void {
  // $ids is the full sequence for that type after the save
}, 10, 2);
```

## Admin behavior

On an enabled `edit.php` screen, the main list is ordered by `menu_order` then publish date. A narrow handle column appears after the checkbox. Drag a row, or focus the handle and use **Arrow Up** / **Arrow Down**. Each change POSTs to `reorder/v1/posts`.

Reordering is disabled (handle hidden, query left alone) when the visible set is not the unfiltered list: search, author, status, month, taxonomy, or a column sort other than menu order.

Saving a page splices that page into the authoritative full sequence (so pagination is honored). If the submitted IDs no longer match that page — a concurrent edit, a deleted post — the request returns **409** and the table restores the previous row order.

This plugin does **not** autosort public queries. WordPress still defaults to `post_date`. Ask for `menu_order` in the query (or use CloakWP’s ACF Query field defaults).

## Architecture

```
src/Core/          # Config, eligibility, sequence, persistence
src/Plugin/        # List table, REST, assets
Reorder.php
```

## Development

```bash
composer install
composer test
```

## License

LGPL-3.0-only
