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

use Symfony\UX\Threejs\Mesh;
use Symfony\UX\Threejs\Light\Light;
use Symfony\UX\Threejs\Model\Model;
use Symfony\UX\Threejs\Camera\Camera;

/**
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 *
 * @final
 */
class Three
{

    public function __construct( 
        public int $width = 300,
        public int $height = 300,
        public Renderer $renderer = new Renderer(),
        ) {
        $this->renderer->scene ??= new Scene();
        $this->renderer->width ??= $this->width;
        $this->renderer->height ??= $this->height;

    }

    public function addCamera(Camera $camera): self {
        $this->renderer->addCamera($camera);
        
        return $this;
    }

    public function addLight(Light $light): self {
        $this->getScene()->addLight($light);
        
        return $this;
    }

    public function addMesh(Mesh $mesh): self {
        $this->renderer->scene->addMesh(
            $mesh,
        );
  
        return $this;
    }

    public function addModel(Model $model): self
    {
        $this->renderer->scene->addModel($model);

        return $this;
    }

    public function getModels()
    {
        return $this->renderer->scene->models;
    }

    public function getScene(): Scene 
    {
        return $this->renderer->scene;
    }

    public function setScene(Scene $scene): self {
        $this->renderer->scene = clone $scene;

        return $this;
    }

    public function createThree(): Three
    {
        return $this;
    }

    public static function fromArray(array $three): self
    {
        $three['renderer'] = Renderer::fromArray($three['renderer']);

        return new self(...$three);
    }

    public function toArray(): array
    {

        return [
            'width' => $this->width,
            'height' => $this->height,
            'renderer' => $this->renderer->toArray(),
        ];
    }

}
