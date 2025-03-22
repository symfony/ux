<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Threejs\Geometry;

/**
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 *
 */
final class Plane extends BufferGeometry
{
   public const string TYPE = 'Plane';

   public function __construct(
      public float $width = 1,
      public float $height = 1,
      public int $widthSegments = 1,
      public int $heightSegments = 1,
   ) {
      parent::__construct();
      $this->type = self::TYPE;
   }
}
