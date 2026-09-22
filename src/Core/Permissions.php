<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Core;

final class Permissions
{
  public function canReorderType(string $postType): bool
  {
    $object = get_post_type_object($postType);
    if (!$object || !isset($object->cap->edit_posts)) {
      return false;
    }

    return current_user_can($object->cap->edit_posts);
  }

  public function canReorderPost(int $id): bool
  {
    return $id > 0 && current_user_can('edit_post', $id);
  }

  /**
   * @param list<int> $ids
   */
  public function canReorderPosts(array $ids): bool
  {
    foreach ($ids as $id) {
      if (!$this->canReorderPost((int) $id)) {
        return false;
      }
    }

    return $ids !== [];
  }
}
