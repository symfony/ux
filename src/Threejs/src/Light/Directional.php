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
final class Directional extends Light
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
   
   public static function fromArray(array $light): self
   {
      $light['position'] = Vector3::fromArray($light['position']);
      $light['target'] = Vector3::fromArray($light['target']);
      $type = $light['type'];
      unset($light['type']);
      $lightObject = new static(...$light);
      $lightObject->type = $type;

      return $lightObject;
   }

   public function toArray(): array
   {
       return [
           'type' => $this->type,
           'color' => $this->color,
           'intensity' => $this->intensity,
           'position' => $this->position->toArray(),
           'target' => $this->target->toArray(),
       ];
   }
}