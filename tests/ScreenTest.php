<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Tests;

use CloakWP\Reorder\Core\Config;
use CloakWP\Reorder\Plugin\Admin\Screen;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WP_Screen;

final class ScreenTest extends TestCase
{
  protected function setUp(): void
  {
    WpStubs::reset();
    WpStubs::postType('project');
    WpStubs::$taxonomies['project'] = ['project_type'];
  }

  public function testReorderableOnAnUnfilteredEditScreen(): void
  {
    $screen = $this->editScreen();
    $admin = new Screen(Config::defaults()->withPostTypes(['project']));

    $this->assertTrue($admin->isEnabled($screen));
    $this->assertTrue($admin->isReorderable($screen));
  }

  public function testDisabledWhenTheTypeIsNotConfigured(): void
  {
    $screen = $this->editScreen();
    $admin = new Screen(Config::defaults());

    $this->assertFalse($admin->isEnabled($screen));
    $this->assertFalse($admin->isReorderable($screen));
  }

  #[DataProvider('filteredQueryProvider')]
  public function testNotReorderableWhenTheVisibleSetIsFiltered(array $query): void
  {
    $_GET = $query;
    $screen = $this->editScreen();
    $admin = new Screen(Config::defaults()->withPostTypes(['project']));

    $this->assertTrue($admin->isEnabled($screen));
    $this->assertFalse($admin->isReorderable($screen));
  }

  /**
   * @return array<string, list<array<string, string>>>
   */
  public static function filteredQueryProvider(): array
  {
    return [
      'search' => [['s' => 'cabin']],
      'status' => [['post_status' => 'draft']],
      'author' => [['author' => '2']],
      'month' => [['m' => '202401']],
      'column sort' => [['orderby' => 'title']],
      'taxonomy' => [['project_type' => '12']],
      'category' => [['cat' => '4']],
    ];
  }

  public function testMenuOrderColumnSortStillAllowsReordering(): void
  {
    $_GET = ['orderby' => 'menu_order'];
    $screen = $this->editScreen();
    $admin = new Screen(Config::defaults()->withPostTypes(['project']));

    $this->assertTrue($admin->isReorderable($screen));
  }

  public function testPerPageFallsBackToTwenty(): void
  {
    $admin = new Screen(Config::defaults()->withPostTypes(['project']));

    $this->assertSame(20, $admin->perPage('project'));

    WpStubs::$userOptions['edit_project_per_page'] = 50;
    $this->assertSame(50, $admin->perPage('project'));
  }

  public function testPerPageUsesTheSamePostsPerPageFilterAsTheList(): void
  {
    WpStubs::$userOptions['edit_project_per_page'] = 50;
    WpStubs::$filterValues['edit_posts_per_page'] = 15;
    $admin = new Screen(Config::defaults()->withPostTypes(['project']));

    $this->assertSame(15, $admin->perPage('project'));
  }

  private function editScreen(): WP_Screen
  {
    $screen = new WP_Screen();
    $screen->base = 'edit';
    $screen->post_type = 'project';

    return $screen;
  }
}
