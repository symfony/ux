<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Threejs;

use Symfony\UX\Threejs\Geometry\Box;
use Symfony\UX\Threejs\Utils\Animation;
use Symfony\UX\Threejs\Material\Material;
use Symfony\UX\Threejs\Material\MeshBasic;
use Symfony\UX\Threejs\Geometry\BufferGeometry;
use Symfony\UX\Threejs\Utils\Vector3;

/**
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 *
 * @final
 */
final class Mesh
{

   public function __construct(
      public ?BufferGeometry $geometry = new Box(),
      public ?Material $material = new MeshBasic(),
      public ?Animation $animation = new Animation(),
      public Vector3 $position = new Vector3(),
      public Vector3 $angle = new Vector3(),
   ) {}

   public function setGeometry(BufferGeometry $geometry): self
   {
      $this->geometry = $geometry;

      return $this;
   }

   public function setAnimation(Animation $animation): self
   {
      $this->animation = $animation;

      return $this;
   }

   public function setMaterial(Material $material): self
   {
      $this->material = $material;

      return $this;
   }

   public function setAngle(float $aX = 0, float $aY = 0, float $aZ = 0): self
   {
      $this->angle->x = $aX;
      $this->angle->y = $aY;
      $this->angle->z = $aZ;

      return $this;
   }

   public function setPosition(float $x = 0, float $y = 0, float $z = 0): self
   {
      $this->position->x = $x;
      $this->position->y = $y;
      $this->position->z = $z;

      return $this;
   }

   public static function fromArray(array $mesh): self
   {
      $mesh['material'] = ('Symfony\\UX\\Threejs\\Material\\'.$mesh['material']['type'])::fromArray($mesh['material']);
      $mesh['geometry'] = ('Symfony\\UX\\Threejs\\Geometry\\'.$mesh['geometry']['type'])::fromArray($mesh['geometry']);
      $mesh['animation'] = Animation::fromArray($mesh['animation']);
      $mesh['position'] = Vector3::fromArray($mesh['position']);
      $mesh['angle'] = Vector3::fromArray($mesh['angle']);

      return new static(...$mesh);
   }

   public function toArray(): array
   {
      return [
         'material' => $this->material->toArray(),
         'geometry' => $this->geometry->toArray(),
         'animation' => $this->animation->toArray(),
         'position' => $this->position->toArray(),
         'angle' => $this->angle->toArray(),
      ];
   }
}
