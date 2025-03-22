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

/**
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 */
abstract class Light
{
   public string $type;
   
   public function __construct(
      public string $color = 'white',
      public float $intensity = 1,
   ) {}
}
