<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Tests;

use CloakWP\Reorder\Reorder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ReorderFacadeTest extends TestCase
{
  protected function setUp(): void
  {
    WpStubs::reset();
    Reorder::reset();
    WpStubs::postType('project');
    WpStubs::postType('service');
  }

  protected function tearDown(): void
  {
    Reorder::reset();
  }

  public function testFluentConfigBuildsExpectedValues(): void
  {
    $instance = Reorder::make()->postTypes(['project', 'service']);

    $this->assertSame(['project', 'service'], $instance->config()->postTypes);
    $this->assertTrue($instance->config()->enables('project'));
    $this->assertFalse(Reorder::booted());
  }

  public function testAllPostTypesEnablesEligibleTypes(): void
  {
    $config = Reorder::make()->allPostTypes()->config();

    $this->assertTrue($config->enables('project'));
    $this->assertContains('service', $config->enabledPostTypes());
  }

  public function testPostTypesRejectsAnEmptyList(): void
  {
    $this->expectException(InvalidArgumentException::class);
    Reorder::make()->postTypes([]);
  }

  public function testRegisterIsIdempotentOnTheSameInstance(): void
  {
    $instance = Reorder::make()->postTypes(['project']);
    $instance->register();
    $instance->register();

    $this->assertTrue(Reorder::booted());
    $this->assertSame($instance, Reorder::instance());
  }
}
