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

use Symfony\UX\Cropperjs\Model\Crop;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @experimental
 */
interface CropperInterface
{
    /**
     * Create a Crop model instance for the given filename.
     *
     * This model instance is used to display the Cropperjs UI and
     * collect the crop details through the form.
     */
    public function createCrop(string $filename): Crop;
}
