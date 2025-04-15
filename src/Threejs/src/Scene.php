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
use Symfony\UX\Threejs\Light\Ambient;
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
      public array $lights = [new Ambient()],
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

   public function addLight(Light $light = new Ambient()): self
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

   
   public static function fromArray(array $scene): self
   {
       $scene['material'] = ('Symfony\\UX\\Threejs\\Material\\'.$scene['material']['type'])::fromArray($scene['material']) ;
       $scene['lights'] = array_map(fn($light) => ('Symfony\\UX\\Threejs\\Light\\'.$light['type'])::fromArray($light), $scene['lights']);
       $scene['meshes'] = array_map(fn($mesh) => Mesh::fromArray($mesh), $scene['meshes']);
       $scene['models'] = array_map(fn($model) => ('Symfony\\UX\\Threejs\\Model\\'.$model['type'])::fromArray($model), $scene['models']);

       return new self(...$scene);
   }

   public function toArray(): array
   {
       return [
           'material' => $this->material->toArray(),
           'lights' => array_map(fn($light) => $light->toArray(), $this->lights),
           'meshes' => array_map(fn($mesh) => $mesh->toArray(), $this->meshes),
           'models' => array_map(fn($model) => $model->toArray(), $this->models),
       ];
   }
}
