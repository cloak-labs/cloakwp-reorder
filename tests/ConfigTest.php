<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Tests;

use CloakWP\Reorder\Core\Config;
use CloakWP\Reorder\Core\PostTypePolicy;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
  protected function setUp(): void
  {
    WpStubs::reset();
    WpStubs::postType('project');
    WpStubs::postType('service');
    WpStubs::postType('page', true, true);
    WpStubs::postType('attachment');
  }

  public function testDefaultsEnableNothing(): void
  {
    $config = Config::defaults();

    $this->assertSame(PostTypePolicy::None, $config->policy);
    $this->assertSame([], $config->postTypes);
    $this->assertFalse($config->enables('project'));
    $this->assertSame([], $config->enabledPostTypes());
  }

  public function testWithersAreImmutable(): void
  {
    $base = Config::defaults();
    $allowlist = $base->withPostTypes(['project', 'service']);
    $all = $base->withAllPostTypes();

    $this->assertSame(PostTypePolicy::None, $base->policy);
    $this->assertTrue($allowlist->enables('project'));
    $this->assertFalse($allowlist->enables('page'));
    $this->assertTrue($all->enables('project'));
    $this->assertFalse($all->enables('page'));
    $this->assertFalse($all->enables('attachment'));
  }

  public function testAllowlistIgnoresUnknownAndIneligibleTypes(): void
  {
    $config = Config::defaults()->withPostTypes(['project', 'page', 'ghost']);

    $this->assertTrue($config->enables('project'));
    $this->assertFalse($config->enables('page'));
    $this->assertFalse($config->enables('ghost'));
    $this->assertSame(['project'], $config->enabledPostTypes());
  }
}
