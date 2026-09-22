<?php

declare(strict_types=1);

namespace CloakWP\Reorder;

use CloakWP\Reorder\Core\Config;
use CloakWP\Reorder\Plugin\Plugin;
use InvalidArgumentException;

/**
 * Fluent entry point for Reorder.
 *
 * @example
 * Reorder::make()
 *   ->postTypes(['project', 'service'])
 *   ->register();
 *
 * @example
 * Reorder::make()
 *   ->allPostTypes()
 *   ->register();
 */
final class Reorder
{
  private static ?self $instance = null;

  private Config $config;
  private bool $registered = false;
  private ?Plugin $plugin = null;

  private function __construct(Config $config)
  {
    $this->config = $config;
  }

  public static function make(): self
  {
    return new self(Config::defaults());
  }

  public static function booted(): bool
  {
    return self::$instance !== null && self::$instance->registered;
  }

  public static function instance(): ?self
  {
    return self::$instance;
  }

  /** @internal */
  public static function reset(): void
  {
    self::$instance = null;
  }

  /**
   * @param list<string> $postTypes
   */
  public function postTypes(array $postTypes): self
  {
    $this->assertMutable();
    if ($postTypes === []) {
      throw new InvalidArgumentException('postTypes() requires at least one post type.');
    }
    $this->config = $this->config->withPostTypes($postTypes);

    return $this;
  }

  public function allPostTypes(): self
  {
    $this->assertMutable();
    $this->config = $this->config->withAllPostTypes();

    return $this;
  }

  public function config(): Config
  {
    return $this->config;
  }

  public function register(): self
  {
    if ($this->registered) {
      return $this;
    }

    if (self::$instance !== null && self::$instance->registered && self::$instance !== $this) {
      throw new InvalidArgumentException(
        'Reorder is already registered. Call Reorder::make()->…->register() only once.',
      );
    }

    /** @var Config $config */
    $config = apply_filters('cloakwp/reorder/config', $this->config);
    $this->config = $config;

    $pluginFile = defined('CLOAKWP_REORDER_FILE')
      ? CLOAKWP_REORDER_FILE
      : dirname(__DIR__) . '/reorder.php';

    $this->plugin = new Plugin($this->config, $pluginFile);
    $this->plugin->boot();

    $this->registered = true;
    self::$instance = $this;

    return $this;
  }

  private function assertMutable(): void
  {
    if ($this->registered) {
      throw new InvalidArgumentException('Cannot change Reorder config after register().');
    }
  }
}
