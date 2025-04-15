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
final class Animation
{
   public Vector3 $rotation;
   public Vector3 $translation;
   public Vector3 $scale;
   
   public function __construct(
      public ?string $playClip = null,
   ) {
      $this->rotation = new Vector3();
      $this->translation = new Vector3();
      $this->scale = new Vector3();
   }

   public function rotate(float $rX = 0, float $rY = 0, float $rZ = 0): self
   {
      $this->rotation->x += $rX;
      $this->rotation->y += $rY;
      $this->rotation->z += $rZ;

      return $this;
   }

   public function translate(float $tX = 0, float $tY = 0, float $tZ = 0): self {
      $this->translation->x += $tX;
      $this->translation->y += $tY;
      $this->translation->z += $tZ;

      return $this;
   }

   public function scale(float $sX = 0, float $sY = 0, float $sZ = 0): self {
      $this->scale->x += $sX;
      $this->scale->y += $sY;
      $this->scale->z += $sZ;

      return $this;
   }

   public static function fromArray(array $animation): self
   {
      $animation['rotation'] = Vector3::fromArray($animation['rotation']);
      $animation['translation'] = Vector3::fromArray($animation['translation']);
      $animation['scale'] = Vector3::fromArray($animation['scale']);
      $animationObject = new self($animation['playClip']);
      $animationObject->rotate($animation['rotation']->x, $animation['rotation']->y, $animation['rotation']->z);
      $animationObject->translate($animation['translation']->x, $animation['translation']->y, $animation['translation']->z);
      $animationObject->scale($animation['scale']->x, $animation['scale']->y, $animation['scale']->z);

      return $animationObject;
   }

   public function toArray(): array
   {
       return [
           'playClip' => $this->playClip,
           'rotation' => $this->rotation->toArray(),
           'translation' => $this->translation->toArray(),
           'scale' => $this->scale->toArray(),
       ];
   }
}
