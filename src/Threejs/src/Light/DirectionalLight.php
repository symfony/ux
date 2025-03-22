<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Threejs\Light;

use Symfony\UX\Threejs\Utils\Vector3;

/**
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 */
final class DirectionalLight extends Light
{
   public const string TYPE = 'Directional';

   public string $type = self::TYPE;

   public function __construct(
      public string $color, 
      public float $intensity,
      public Vector3 $position = new Vector3(),
      public Vector3 $target = new Vector3(),
   
   ) {
      parent::__construct($color, $intensity);
   }
}