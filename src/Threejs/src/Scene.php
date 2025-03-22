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

use Symfony\UX\Threejs\Light\Light;
use Symfony\UX\Threejs\Model\Model;
use Symfony\UX\Threejs\Material\Material;
use Symfony\UX\Threejs\Light\AmbientLight;
use Symfony\UX\Threejs\Material\MeshBasic;

/**
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 *
 * @final
 */
final class Scene
{
   public function __construct(
      public ?Material $material = new MeshBasic(),
      public array $lights = [new AmbientLight()],
      public array $meshes = [],
      public array $models = [],
   )
   {
   }   
    
   public function setMaterial(Material $material): self
   {
      $this->material = $material;

      return $this;
   }

   public function addLight(Light $light = new AmbientLight()): self
   {
      $this->lights[] = $light;

      return $this;
   }

   public function addMesh(Mesh $mesh): self {
      $this->meshes[] = $mesh;

      return $this;
   }

   public function addModel(Model $model): self {
      $this->models[] = $model;

      return $this;
   }
}
