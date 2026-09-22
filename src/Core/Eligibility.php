<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Core;

/**
 * Attachments and internals stay out. Core pages keep their tree UI.
 * Custom types may be hierarchical in WordPress but still use a flat
 * list table — those are eligible; we only write menu_order, never parent.
 */
final class Eligibility
{
  /** @var list<string> */
  public const EXCLUDED = [
    'attachment',
    'page',
    'revision',
    'nav_menu_item',
    'custom_css',
    'customize_changeset',
    'oembed_cache',
    'user_request',
    'wp_block',
    'wp_template',
    'wp_template_part',
    'wp_global_styles',
    'wp_navigation',
    'wp_font_family',
    'wp_font_face',
    'acf-field',
    'acf-field-group',
    'acf-post-type',
    'acf-taxonomy',
    'acf-ui-options-page',
  ];

  public function allows(string $postType): bool
  {
    if ($postType === '' || in_array($postType, self::EXCLUDED, true)) {
      return false;
    }

    if (!function_exists('get_post_type_object')) {
      return false;
    }

    $object = get_post_type_object($postType);
    if (!$object || empty($object->show_ui)) {
      return false;
    }

    return true;
  }

  /**
   * @return list<string>
   */
  public function all(): array
  {
    if (!function_exists('get_post_types')) {
      return [];
    }

    $names = get_post_types(['show_ui' => true], 'names');
    if (!is_array($names)) {
      return [];
    }

    $eligible = [];
    foreach ($names as $name) {
      $slug = (string) $name;
      if ($this->allows($slug)) {
        $eligible[] = $slug;
      }
    }

    return $eligible;
  }
}
