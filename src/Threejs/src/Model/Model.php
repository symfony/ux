<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Threejs\Model;

use Symfony\UX\Threejs\Utils\Animation;
use Symfony\UX\Threejs\Utils\Vector3;

/**
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 *
 */
abstract class Model
{
   public string $type;

   public function __construct(
      public string $path,
      public Vector3 $position = new Vector3(),
      public Vector3 $angle = new Vector3(),
      public Animation $animation = new Animation(),  
   ) {
   }

   public static function fromArray(array $model): self
   {
      $model['position'] = Vector3::fromArray($model['position']);
      $model['angle'] = Vector3::fromArray($model['angle']);
      $model['animation'] = Animation::fromArray($model['animation']);

      $type = $model['type'];
      unset($model['type']);
      $modelObject = new static(...$model);
      $modelObject->type = $type;

      return $modelObject;
   }

   public function toArray(): array
   {
       return [
           'path' => $this->path,
           'position' => $this->position,
           'angle' => $this->angle,
           'type' => $this->type,
           'animation' => $this->animation->toArray(),
       ];
   }
}
