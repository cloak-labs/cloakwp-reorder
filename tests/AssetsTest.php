<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Tests;

use CloakWP\Reorder\Plugin\Assets;
use PHPUnit\Framework\TestCase;

final class AssetsTest extends TestCase
{
  protected function setUp(): void
  {
    WpStubs::reset();
  }

  public function testRewritesSymlinkedPluginUrlsToTheMuPluginPath(): void
  {
    if (!defined('WPMU_PLUGIN_URL')) {
      define('WPMU_PLUGIN_URL', 'https://example.test/app/mu-plugins');
    }

    WpStubs::$pluginsUrlBase = 'https://example.test/app/plugins/var/www/reorder';
    $assets = new Assets('/tmp/reorder.php');

    $this->assertSame(
      'https://example.test/app/mu-plugins/reorder/resources/js/edit-list.js',
      $assets->url('resources/js/edit-list.js'),
    );
  }

  public function testEnqueueLocalizesTheRestEndpoint(): void
  {
    $assets = new Assets(dirname(__DIR__) . '/reorder.php');
    $assets->enqueue('project', 2, 20);

    $payload = json_decode(str_replace('window.reorderList = ', '', rtrim(WpStubs::$inlineScripts[Assets::SCRIPT_HANDLE], ';')), true);

    $this->assertContains(Assets::SCRIPT_HANDLE, WpStubs::$enqueuedScripts);
    $this->assertContains(Assets::STYLE_HANDLE, WpStubs::$enqueuedStyles);
    $this->assertSame('project', $payload['postType']);
    $this->assertSame(2, $payload['paged']);
    $this->assertSame(
      'https://example.test/wp-json/reorder/v1/posts',
      $payload['restUrl'],
    );
  }
}
