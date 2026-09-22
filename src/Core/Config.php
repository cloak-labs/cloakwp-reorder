<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Core;

use InvalidArgumentException;

/**
 * Immutable plugin configuration. Defaults enable nothing.
 */
final class Config
{
  /**
   * @param list<string> $postTypes
   */
  public function __construct(
    public readonly PostTypePolicy $policy,
    public readonly array $postTypes,
    public readonly Eligibility $eligibility = new Eligibility(),
  ) {
  }

  public static function defaults(): self
  {
    return new self(PostTypePolicy::None, []);
  }

  /**
   * @param list<string> $postTypes
   */
  public function withPostTypes(array $postTypes): self
  {
    $slugs = [];
    foreach ($postTypes as $postType) {
      $slug = sanitize_key((string) $postType);
      if ($slug === '') {
        throw new InvalidArgumentException('Post type slugs cannot be empty.');
      }
      $slugs[] = $slug;
    }

    return new self(
      PostTypePolicy::Allowlist,
      array_values(array_unique($slugs)),
      $this->eligibility,
    );
  }

  public function withAllPostTypes(): self
  {
    return new self(PostTypePolicy::All, [], $this->eligibility);
  }

  public function enables(string $postType): bool
  {
    $slug = sanitize_key($postType);
    if ($slug === '' || !$this->eligibility->allows($slug)) {
      return false;
    }

    return match ($this->policy) {
      PostTypePolicy::None => false,
      PostTypePolicy::All => true,
      PostTypePolicy::Allowlist => in_array($slug, $this->postTypes, true),
    };
  }

  /**
   * @return list<string>
   */
  public function enabledPostTypes(): array
  {
    return match ($this->policy) {
      PostTypePolicy::None => [],
      PostTypePolicy::All => $this->eligibility->all(),
      PostTypePolicy::Allowlist => array_values(array_filter(
        $this->postTypes,
        fn(string $postType): bool => $this->eligibility->allows($postType),
      )),
    };
  }
}
