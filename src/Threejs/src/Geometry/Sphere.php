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
final class Sphere extends BufferGeometry
{
   public const string TYPE = 'Sphere';

   public function __construct(
      public float $radius = 1,
      public int $widthSegments = 32,
      public int $heightSegments = 16,
   ) {
      parent::__construct();
      $this->type = self::TYPE;
   }

   public function toArray(): array
   {
        return [
          'radius' => $this->radius,
          'widthSegments' => $this->widthSegments,
          'heightSegments' => $this->heightSegments,
          'type' => $this->type,
      ]; 
   }
}
