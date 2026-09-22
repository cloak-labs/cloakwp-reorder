<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Core;

use RuntimeException;

final class StalePageException extends RuntimeException
{
  public static function mismatch(): self
  {
    return new self('The list has changed. Refresh and try again.');
  }
}
