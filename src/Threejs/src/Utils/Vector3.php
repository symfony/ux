<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Threejs\Utils;

/**
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 *
 */
final class Vector3
{
   public function __construct(
      public float $x = 0,
      public float $y = 0,
      public float $z = 0,
   ) {}
}
