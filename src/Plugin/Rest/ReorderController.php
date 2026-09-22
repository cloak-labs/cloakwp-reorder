<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Plugin\Rest;

use CloakWP\Reorder\Core\Config;
use CloakWP\Reorder\Core\Permissions;
use CloakWP\Reorder\Core\PostSequenceRepository;
use CloakWP\Reorder\Core\StalePageException;
use CloakWP\Reorder\Plugin\Admin\Screen;
use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class ReorderController
{
  public function __construct(
    private readonly Config $config,
    private readonly Permissions $permissions,
    private readonly PostSequenceRepository $repository,
    private readonly Screen $screen,
  ) {
  }

  public function register(): void
  {
    add_action('rest_api_init', [$this, 'registerRoutes']);
  }

  public function registerRoutes(): void
  {
    register_rest_route('reorder/v1', '/posts', [
      'methods' => 'POST',
      'callback' => [$this, 'handle'],
      'permission_callback' => [$this, 'canReorder'],
      'args' => [
        'post_type' => [
          'required' => true,
          'type' => 'string',
        ],
        'ids' => [
          'required' => true,
          'type' => 'array',
          'items' => ['type' => 'integer'],
        ],
        'paged' => [
          'required' => false,
          'type' => 'integer',
          'default' => 1,
        ],
      ],
    ]);
  }

  public function canReorder(WP_REST_Request $request): bool
  {
    $postType = sanitize_key((string) $request->get_param('post_type'));

    return $postType !== ''
      && $this->config->enables($postType)
      && $this->permissions->canReorderType($postType);
  }

  public function handle(WP_REST_Request $request): WP_REST_Response|WP_Error
  {
    $postType = sanitize_key((string) $request->get_param('post_type'));
    $paged = max(1, (int) $request->get_param('paged'));
    $ids = $this->positiveInts($request->get_param('ids'));

    if ($ids === []) {
      return new WP_Error(
        'reorder_invalid_ids',
        __('Invalid order data.', 'reorder'),
        ['status' => 400],
      );
    }

    if (!$this->permissions->canReorderPosts($ids)) {
      return new WP_Error(
        'reorder_forbidden',
        __('You cannot reorder one or more items in this list.', 'reorder'),
        ['status' => 403],
      );
    }

    try {
      $sequence = $this->repository->applyPage(
        $postType,
        $ids,
        $paged,
        $this->screen->perPage($postType),
      );
    } catch (StalePageException $e) {
      return new WP_Error(
        'reorder_stale',
        $e->getMessage(),
        ['status' => 409],
      );
    } catch (InvalidArgumentException $e) {
      return new WP_Error(
        'reorder_invalid_ids',
        $e->getMessage(),
        ['status' => 400],
      );
    }

    do_action('cloakwp/reorder/updated', $postType, $sequence->ids());

    return new WP_REST_Response([
      'ids' => $sequence->ids(),
    ], 200);
  }

  /**
   * @return list<int>
   */
  private function positiveInts(mixed $value): array
  {
    if (!is_array($value)) {
      return [];
    }

    $ids = [];
    foreach ($value as $item) {
      $id = (int) $item;
      if ($id > 0 && !in_array($id, $ids, true)) {
        $ids[] = $id;
      }
    }

    return $ids;
  }
}
