<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Async;

use Symfony\UX\Image\ImageAsset;

interface ImageProcessingDispatcherInterface
{
    /**
     * Durably dispatch variant generation for a stored, inspected original.
     *
     * The asset carries its profile and trusted dimensions, has no generated
     * variants yet and keeps profileRevision null. Dispatchers must tolerate
     * repeated calls for the same application-owned asset and profile. Returning
     * means the work was accepted durably; throwing means it was not accepted.
     *
     * @throws \Throwable when the work was not accepted durably
     */
    public function dispatch(ImageAsset $asset, string $profile): void;
}
