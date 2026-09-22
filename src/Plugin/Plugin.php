<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Plugin;

use CloakWP\Reorder\Core\Config;
use CloakWP\Reorder\Core\Permissions;
use CloakWP\Reorder\Core\PostSequenceRepository;
use CloakWP\Reorder\Plugin\Admin\ListTable;
use CloakWP\Reorder\Plugin\Admin\Screen;
use CloakWP\Reorder\Plugin\Rest\ReorderController;

final class Plugin
{
  public function __construct(
    private readonly Config $config,
    private readonly string $pluginFile,
  ) {
  }

  public function boot(): void
  {
    $assets = new Assets($this->pluginFile);
    $screen = new Screen($this->config);
    $permissions = new Permissions();

    (new ListTable($this->config, $screen, $assets))->register();
    (new ReorderController($this->config, $permissions, new PostSequenceRepository(), $screen))->register();
  }
}
