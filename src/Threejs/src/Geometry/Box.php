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
final class Box extends BufferGeometry
{
   public const string TYPE = 'Box';

   public function __construct(
      public float $width = 1, 
      public float $height = 1,
      public float $depth = 1,
   ) {
      parent::__construct();
      $this->type = self::TYPE;
   }



   public function toArray(): array
   {
        return [
          'width' => $this->width,
          'height' => $this->height,
          'depth' => $this->depth,
          'type' => $this->type,
      ]; 
   }
}
