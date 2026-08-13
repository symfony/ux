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

use Symfony\UX\Image\ImageAsset;

final readonly class ImageAssetReference
{
    public function __construct(
        public string $id,
        public string $cursor,
        public string $version,
        public ImageAsset $asset,
    ) {
        if ('' === $id || '' === $cursor || '' === $version) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException('An image asset reference requires a stable id, opaque cursor and version token.');
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCursor(): string
    {
        return $this->cursor;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getAsset(): ImageAsset
    {
        return $this->asset;
    }
}
