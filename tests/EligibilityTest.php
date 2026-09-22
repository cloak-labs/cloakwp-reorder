<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Tests;

use CloakWP\Reorder\Core\Eligibility;
use PHPUnit\Framework\TestCase;

final class EligibilityTest extends TestCase
{
  protected function setUp(): void
  {
    WpStubs::reset();
  }

  public function testAllowsFlatPublicTypesAndRejectsTheRest(): void
  {
    WpStubs::postType('project');
    WpStubs::postType('page', true, true);
    WpStubs::postType('hidden', false);
    WpStubs::postType('attachment');
    WpStubs::postType('wp_block');

    $eligibility = new Eligibility();

    $this->assertTrue($eligibility->allows('project'));
    $this->assertFalse($eligibility->allows('page'));
    $this->assertFalse($eligibility->allows('hidden'));
    $this->assertFalse($eligibility->allows('attachment'));
    $this->assertFalse($eligibility->allows('wp_block'));
    $this->assertSame(['project'], $eligibility->all());
  }

  public function testAllowsHierarchicalCustomTypes(): void
  {
    WpStubs::postType('team', true, true);

    $this->assertTrue((new Eligibility())->allows('team'));
  }
}
