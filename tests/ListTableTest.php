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

  public function testQuickEditPostsAMarkerOnlyWhileTheListIsReorderable(): void
  {
    $table = $this->table();

    ob_start();
    $table->quickEditMarker('reorder', 'project');
    $html = (string) ob_get_clean();

    $this->assertStringContainsString('name="reorder_active"', $html);
    $this->assertStringContainsString('value="1"', $html);

    $_GET = ['s' => 'cabin'];
    ob_start();
    $table->quickEditMarker('reorder', 'project');
    $this->assertSame('', (string) ob_get_clean());
  }

  public function testInlineSaveKeepsTheHandleColumnWithoutACurrentScreen(): void
  {
    WpStubs::$currentScreen = null;
    $_POST = [
      'post_type' => 'project',
      'reorder_active' => '1',
    ];

    $table = $this->table();
    $table->prepareInlineSave();

    $columns = $table->columns(['cb' => '<input />', 'title' => 'Title']);
    $this->assertSame(['cb', 'reorder', 'title'], array_keys($columns));

    ob_start();
    $table->renderColumn('reorder', 31);
    $html = (string) ob_get_clean();
    $this->assertStringContainsString('class="reorder-handle"', $html);

    $hooks = array_column(WpStubs::$filters, 'hook');
    $this->assertContains('manage_project_posts_columns', $hooks);
  }

  public function testInlineSaveOmitsTheHandleWhenTheListWasNotReorderable(): void
  {
    WpStubs::$currentScreen = null;
    $_POST = ['post_type' => 'project'];

    $table = $this->table();
    $table->prepareInlineSave();

    $this->assertSame(
      ['cb', 'title'],
      array_keys($table->columns(['cb' => '<input />', 'title' => 'Title'])),
    );
  }

  public function testInlineSaveIgnoresAMarkerForAPostTypeThatIsNotEnabled(): void
  {
    WpStubs::$currentScreen = null;
    $_POST = [
      'post_type' => 'page',
      'reorder_active' => '1',
    ];

    $table = $this->table();
    $table->prepareInlineSave();

    $this->assertArrayNotHasKey('reorder', $table->columns(['title' => 'Title']));
  }

  public function testHierarchicalListsStayFlatSoTheSavedPageMatchesTheRows(): void
  {
    WpStubs::postType('project', true, true);
    $_GET = ['post_type' => 'project'];

    $this->table()->flattenHierarchicalQuery();

    $this->assertSame('menu_order', $_GET['orderby']);
    $this->assertSame('menu_order', $_REQUEST['orderby']);
  }

  public function testDoesNotFlattenWhenAColumnSortIsAlreadyChosen(): void
  {
    WpStubs::postType('project', true, true);
    $_GET = ['post_type' => 'project', 'orderby' => 'title'];

    $this->table()->flattenHierarchicalQuery();

    $this->assertSame('title', $_GET['orderby']);
  }

  public function testNonHierarchicalListsDoNotInventAnOrderby(): void
  {
    $_GET = ['post_type' => 'project'];

    $this->table()->flattenHierarchicalQuery();

    $this->assertArrayNotHasKey('orderby', $_GET);
  }

  public function testRegistersInlineSaveBeforeCoreRendersTheReplacementRow(): void
  {
    $this->table()->register();

    $hook = null;
    foreach (WpStubs::$actions as $action) {
      if ($action['hook'] === 'wp_ajax_inline-save') {
        $hook = $action;
      }
    }

    $this->assertNotNull($hook);
    $this->assertSame(0, $hook['priority']);
    $this->assertContains('quick_edit_custom_box', array_column(WpStubs::$actions, 'hook'));
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
