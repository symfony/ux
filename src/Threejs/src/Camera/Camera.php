<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Threejs\Camera;

use Symfony\UX\Threejs\Utils\Vector3;

/**
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 *
 */
abstract class Camera
{
    public Vector3 $position;
    public string $type;

    public function __construct(?Vector3 $position = null)
    {
        $this->position = $position ?? new Vector3(0, 0, 5);
    }

    public static function fromArray(array $camera): self
    {
        $camera['position'] = Vector3::fromArray($camera['position']);
        $type = $camera['type'];
        unset($camera['type']);
        $cameraObject = new static(...$camera);
        $cameraObject->type = $type;

        return $cameraObject;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'position' => $this->position->toArray(),
        ];
    }
}
