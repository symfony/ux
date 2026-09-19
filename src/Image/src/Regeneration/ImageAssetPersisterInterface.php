<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Regeneration;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\UX\Image\ImageAsset;

#[AutoconfigureTag('ux_image.regeneration.persister')]
interface ImageAssetPersisterInterface
{
    /**
     * Compare the current application version with the reference version and
     * atomically replace the persisted asset.
     *
     * Return false when the entity changed since the provider read it. An
     * exception must mean that no update became durable. Repeating the same
     * successful replacement must be safe; generated-file cleanup remains the
     * caller's responsibility.
     *
     * @throws \Throwable when no update became durable
     */
    public function compareAndSwap(ImageAssetReference $reference, ImageAsset $asset): bool;
}
