<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Core;

/**
 * Flat list-table reordering cannot express parent/child changes, so hierarchical
 * types and attachments stay out of this release.
 */
final class Eligibility
{
  /** @var list<string> */
  public const EXCLUDED = [
    'attachment',
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
    if (!$object || empty($object->show_ui) || !empty($object->hierarchical)) {
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
