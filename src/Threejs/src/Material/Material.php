<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Threejs\Material;

/**
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 *
 */
abstract class Material
{
   public string $type;

   public function __construct(
      public ?string $color = null,
      public float $opacity = 1,
      public string $map = '',
      public bool $doubleSide = false,
      public bool $skybox = false,
      public bool $transparent = false,
   ) {
      $this->transparent = $this->opacity < 1;
   } 

   public static function fromArray(array $material): self
   {
      $material['transparent'] = $material['opacity'] < 1;
      $type = $material['type'];
      unset($material['type']);
      $materialObject = new static(...$material);
      $materialObject->type = $type;

      return $materialObject;
   }

   public function toArray(): array
   {
       return [
           'transparent' => $this->transparent,
           'type' => $this->type,
           'color' => $this->color,
           'opacity' => $this->opacity,
           'map' => $this->map,
           'doubleSide' => $this->doubleSide,
           'skybox' => $this->skybox,
       ];
   }
}
