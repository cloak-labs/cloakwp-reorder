<?php

declare(strict_types=1);

namespace CloakWP\Reorder\Core;

enum PostTypePolicy
{
  case None;
  case Allowlist;
  case All;
}
