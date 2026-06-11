<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Cropperjs\Factory;

use Intervention\Image\Interfaces\ImageManagerInterface;
use Symfony\UX\Cropperjs\Model\Crop;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 */
class Cropper implements CropperInterface
{
    private ImageManagerInterface $imageManager;

    public function __construct(ImageManagerInterface $imageManager)
    {
        $this->imageManager = $imageManager;
    }

    public function createCrop(string $filename): Crop
    {
        return new Crop($this->imageManager, $filename);
    }
}
