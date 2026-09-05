<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\UrlGenerator;

use Symfony\UX\Image\Exception\ExceptionInterface;
use Symfony\UX\Image\ImageAsset;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface UrlGeneratorInterface
{
    /**
     * Generate the public URL for the original image asset.
     *
     * @throws ExceptionInterface
     */
    public function generateAssetUrl(ImageAsset $asset): string;

    /**
     * Generate the public URL for a specific variant of the image asset.
     *
     * Canonical persisted variants use ImageSource::toArray(). Additional keys
     * are preserved for custom URL adapters and CDN builders.
     *
     * @param array<string, mixed> $variant
     *
     * @throws ExceptionInterface
     */
    public function generateVariantUrl(ImageAsset $asset, array $variant): string;
}
