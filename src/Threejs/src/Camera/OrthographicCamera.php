<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Threejs\Camera;

use Symfony\UX\Threejs\Utils\Vector3;

/**
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 */
final class OrthographicCamera extends Camera
{
   public string $type = 'Orthographic';

   public function __construct(
      public float $left = -1,
      public float $right = 1,
      public float $top = 1,
      public float $bottom = -1,
      public float $near = 0.1,
      public float $far = 10,
      ?Vector3 $position = null,
   ) {
      parent::__construct($position);
   }
}
