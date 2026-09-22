<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Tests;

final class WpStubs
{
  /** @var array<string, object|false> */
  public static array $postTypeObjects = [];

  /** @var array<string, list<string>> */
  public static array $taxonomies = [];

  /** @var array<string, bool> */
  public static array $caps = [];

  /** @var list<array{hook: mixed, callback: mixed, priority: mixed}> */
  public static array $actions = [];

  /** @var list<array{hook: mixed, callback: mixed, priority: mixed}> */
  public static array $filters = [];

  /** @var array<string, mixed> */
  public static array $filterValues = [];

  /** @var list<array{hook: mixed, args: list<mixed>}> */
  public static array $didActions = [];

  /** @var list<array{route: string, args: array<string, mixed>}> */
  public static array $restRoutes = [];

  /** @var array<string, int> */
  public static array $userOptions = [];

  /** @var list<string> */
  public static array $enqueuedScripts = [];

  /** @var list<string> */
  public static array $enqueuedStyles = [];

  /** @var array<string, array{src: string, deps: list<string>, ver: string}> */
  public static array $registeredScripts = [];

  /** @var array<string, array{src: string, deps: list<string>, ver: string}> */
  public static array $registeredStyles = [];

  /** @var array<string, string> */
  public static array $inlineScripts = [];

  /** @var list<int> */
  public static array $cleanedPostIds = [];

  public static string $pluginsUrlBase = 'https://example.test/app/mu-plugins/reorder';

  public static ?object $currentScreen = null;

  public static bool $isAdmin = true;

  public static function reset(): void
  {
    self::$postTypeObjects = [];
    self::$taxonomies = [];
    self::$caps = [];
    self::$actions = [];
    self::$filters = [];
    self::$filterValues = [];
    self::$didActions = [];
    self::$restRoutes = [];
    self::$userOptions = [];
    self::$enqueuedScripts = [];
    self::$enqueuedStyles = [];
    self::$registeredScripts = [];
    self::$registeredStyles = [];
    self::$inlineScripts = [];
    self::$cleanedPostIds = [];
    self::$pluginsUrlBase = 'https://example.test/app/mu-plugins/reorder';
    self::$currentScreen = null;
    self::$isAdmin = true;
    $_GET = [];
  }

  public static function postType(
    string $slug,
    bool $showUi = true,
    bool $hierarchical = false,
    string $editPostsCap = 'edit_posts',
  ): object {
    $object = (object) [
      'name' => $slug,
      'show_ui' => $showUi,
      'hierarchical' => $hierarchical,
      'cap' => (object) ['edit_posts' => $editPostsCap],
    ];
    self::$postTypeObjects[$slug] = $object;

    return $object;
  }
}
