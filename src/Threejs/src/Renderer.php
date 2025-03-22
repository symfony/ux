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

use Symfony\UX\Threejs\Camera\Camera;
use Symfony\UX\Threejs\Camera\PerspectiveCamera;

/**
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 *
 * @final
 */
final class Renderer
{
   public function __construct(
      public Scene $scene = new Scene(),
      public bool $controls = true,
      public array $cameras = [new PerspectiveCamera()],
      public ?int $width = 300,
      public ?int $height = 300,
   ) {}

   public function setCameras(array $cameras): self
   {
      $this->cameras = $cameras;

      return $this;
   }

   public function addCamera(Camera $camera): self
   {
      $this->cameras[] = $camera;

      return $this;
   }
}
