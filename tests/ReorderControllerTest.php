<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Tests;

use CloakWP\Reorder\Core\Config;
use CloakWP\Reorder\Core\Permissions;
use CloakWP\Reorder\Core\PostSequenceRepository;
use CloakWP\Reorder\Plugin\Admin\Screen;
use CloakWP\Reorder\Plugin\Rest\ReorderController;
use PHPUnit\Framework\TestCase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class ReorderControllerTest extends TestCase
{
  protected function setUp(): void
  {
    WpStubs::reset();
    WpStubs::postType('project');
    WpStubs::$caps['edit_posts'] = true;
    WpStubs::$caps['edit_post:4'] = true;
    WpStubs::$caps['edit_post:5'] = true;
    WpStubs::$caps['edit_post:6'] = true;
    WpStubs::$userOptions['edit_project_per_page'] = 3;
  }

  public function testRegistersThePostsRoute(): void
  {
    $this->controller()->register();
    $this->controller()->registerRoutes();

    $this->assertSame('reorder/v1/posts', WpStubs::$restRoutes[0]['route']);
  }

  public function testSavesAValidPageAndFiresTheUpdatedHook(): void
  {
    $table = new FakePostsTable([1, 2, 3, 4, 5, 6]);
    $response = $this->controller($table)->handle(new WP_REST_Request([
      'post_type' => 'project',
      'ids' => [6, 4, 5],
      'paged' => 2,
    ]));

    $this->assertInstanceOf(WP_REST_Response::class, $response);
    $this->assertSame([1, 2, 3, 6, 4, 5], $response->data['ids']);
    $this->assertSame('cloakwp/reorder/updated', WpStubs::$didActions[0]['hook']);
  }

  public function testForbiddenWhenARowCannotBeEdited(): void
  {
    unset(WpStubs::$caps['edit_post:5']);

    $response = $this->controller(new FakePostsTable([4, 5, 6]))->handle(new WP_REST_Request([
      'post_type' => 'project',
      'ids' => [4, 5, 6],
      'paged' => 1,
    ]));

    $this->assertInstanceOf(WP_Error::class, $response);
    $this->assertSame('reorder_forbidden', $response->code);
    $this->assertSame(403, $response->data['status']);
  }

  public function testConflictWhenThePageIsStale(): void
  {
    WpStubs::$caps['edit_post:1'] = true;
    WpStubs::$caps['edit_post:2'] = true;
    WpStubs::$caps['edit_post:4'] = true;

    $response = $this->controller(new FakePostsTable([1, 2, 3]))->handle(new WP_REST_Request([
      'post_type' => 'project',
      'ids' => [1, 2, 4],
      'paged' => 1,
    ]));

    $this->assertInstanceOf(WP_Error::class, $response);
    $this->assertSame('reorder_stale', $response->code);
    $this->assertSame(409, $response->data['status']);
  }

  public function testPermissionCallbackRequiresAnEnabledType(): void
  {
    $controller = $this->controller();

    $this->assertTrue($controller->canReorder(new WP_REST_Request(['post_type' => 'project'])));
    $this->assertFalse($controller->canReorder(new WP_REST_Request(['post_type' => 'page'])));
  }

  public function testPermissionCallbackRejectsUsersWithoutEditCaps(): void
  {
    WpStubs::$caps['edit_posts'] = false;

    $this->assertFalse(
      $this->controller()->canReorder(new WP_REST_Request(['post_type' => 'project'])),
    );
  }

  private function controller(?FakePostsTable $table = null): ReorderController
  {
    $config = Config::defaults()->withPostTypes(['project']);

    return new ReorderController(
      $config,
      new Permissions(),
      new PostSequenceRepository($table ?? new FakePostsTable([1, 2, 3, 4, 5, 6])),
      new Screen($config),
    );
  }
}
