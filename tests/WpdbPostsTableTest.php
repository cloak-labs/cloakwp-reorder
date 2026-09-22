<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Tests;

use CloakWP\Reorder\Core\Sequence;
use CloakWP\Reorder\Core\WpdbPostsTable;
use PHPUnit\Framework\TestCase;

final class WpdbPostsTableTest extends TestCase
{
  protected function setUp(): void
  {
    WpStubs::reset();
    $GLOBALS['wpdb'] = new FakeWpdb();
  }

  protected function tearDown(): void
  {
    unset($GLOBALS['wpdb']);
  }

  public function testPersistRenumbersMenuOrderAndClearsPostCaches(): void
  {
    $table = new WpdbPostsTable();
    $table->persist(new Sequence([4, 5, 6]));

    $this->assertSame(
      [
        ['ID' => 4, 'menu_order' => 0],
        ['ID' => 5, 'menu_order' => 1],
        ['ID' => 6, 'menu_order' => 2],
      ],
      $GLOBALS['wpdb']->updates,
    );
    $this->assertSame([4, 5, 6], WpStubs::$cleanedPostIds);
  }
}

final class FakeWpdb
{
  public string $posts = 'wp_posts';
  public string $last_error = '';

  /** @var list<array{ID: int, menu_order: int}> */
  public array $updates = [];

  public function update($table, $data, $where, $format = null, $whereFormat = null): int
  {
    $this->updates[] = [
      'ID' => (int) $where['ID'],
      'menu_order' => (int) $data['menu_order'],
    ];

    return 1;
  }
}
