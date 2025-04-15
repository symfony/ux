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
final class Cylinder extends BufferGeometry
{
   public const string TYPE = 'Cylinder';

   public function __construct(
      public float $radiusTop = 1,
      public float $radiusBottom = 1,
      public float $height = 1,
      public int $radialSegments = 32,
      public int $heightSegments = 1,
      public bool $openEnded = false,
      public float $thetaStart = 0,
      public float $thetaLength = 2 * M_PI,
  ) {
      parent::__construct();
      $this->type = self::TYPE;
   }

   public function toArray(): array
   {
        return [
          'radiusTop' => $this->radiusTop,
          'radiusBottom' => $this->radiusBottom,
          'height' => $this->height,
          'radialSegments' => $this->radialSegments,
          'heightSegments' => $this->heightSegments,
          'openEnded' => $this->openEnded,
          'thetaStart' => $this->thetaStart,
          'thetaLength' => $this->thetaLength,
      ]; 
   }
}


