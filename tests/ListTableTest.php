<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Tests;

use CloakWP\Reorder\Core\Config;
use CloakWP\Reorder\Plugin\Admin\ListTable;
use CloakWP\Reorder\Plugin\Admin\Screen;
use CloakWP\Reorder\Plugin\Assets;
use PHPUnit\Framework\TestCase;
use WP_Query;
use WP_Screen;

final class ListTableTest extends TestCase
{
  protected function setUp(): void
  {
    WpStubs::reset();
    WpStubs::postType('project');
    WpStubs::$currentScreen = $this->editScreen();
  }

  public function testInsertsAHandleColumnWhenReorderable(): void
  {
    $columns = $this->table()->columns(['cb' => '<input />', 'title' => 'Title']);

    $this->assertSame(['cb', 'reorder', 'title'], array_keys($columns));
  }

  public function testLeavesColumnsAloneWhenTheListIsFiltered(): void
  {
    $_GET = ['s' => 'cabin'];
    $original = ['cb' => '<input />', 'title' => 'Title'];

    $this->assertSame($original, $this->table()->columns($original));
  }

  public function testOrdersTheAdminQueryByMenuOrderThenDate(): void
  {
    $query = new WP_Query();
    $this->table()->orderQuery($query);

    $this->assertSame([
      'menu_order' => 'ASC',
      'date' => 'DESC',
    ], $query->vars['orderby']);
  }

  public function testDoesNotOrderFilteredAdminQueries(): void
  {
    $_GET = ['orderby' => 'title'];
    $query = new WP_Query();
    $this->table()->orderQuery($query);

    $this->assertArrayNotHasKey('orderby', $query->vars);
  }

  public function testHandleIsAFocusableSpanNotAButton(): void
  {
    ob_start();
    $this->table()->renderColumn('reorder', 31);
    $html = (string) ob_get_clean();

    $this->assertStringContainsString('class="reorder-handle"', $html);
    $this->assertStringContainsString('role="button"', $html);
    $this->assertStringNotContainsString('<button', $html);
  }

  private function table(): ListTable
  {
    $config = Config::defaults()->withPostTypes(['project']);

    return new ListTable($config, new Screen($config), new Assets('/tmp/reorder.php'));
  }

  private function editScreen(): WP_Screen
  {
    $screen = new WP_Screen();
    $screen->base = 'edit';
    $screen->post_type = 'project';

    return $screen;
  }
}
