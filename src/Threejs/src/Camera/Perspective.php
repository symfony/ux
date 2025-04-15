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
final class Perspective extends Camera
{
   public string $type = 'Perspective';

   public function __construct(
      public float $fov = 75,
      public float $near = 0.1,
      public float $far = 1000,
      ?Vector3 $position = null,
   ) {
      parent::__construct($position);
   }
}
