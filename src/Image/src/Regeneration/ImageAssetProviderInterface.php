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

#[AutoconfigureTag('ux_image.regeneration.provider')]
interface ImageAssetProviderInterface
{
    /**
     * Fetch at most query.limit assets in stable keyset order.
     *
     * The opaque query.after cursor is exclusive. A non-null next cursor must
     * resume after the final returned reference without duplicates. Providers
     * must not derive identity or ordering from mutable filenames.
     *
     * @throws \Throwable when no complete batch can be returned
     */
    public function fetch(ImageAssetBatchQuery $query): ImageAssetBatch;
}
