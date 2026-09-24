<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Processor;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Image\Exception\ExceptionInterface;
use Symfony\UX\Image\ImageAsset;

/**
 * Interface for image processing operations.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface ImageProcessorInterface
{
    /**
     * Process an uploaded image file according to the given profile.
     *
     * @throws ExceptionInterface
     */
    public function process(UploadedFile $file, ?string $profile = null, string $storage = 'default_public'): ImageAsset;

    /**
     * Generate variants for an existing image asset.
     *
     * @param array<string, mixed> $variantConfigs
     *
     * @return array<string, list<array{name: string, path: string, format: string, mimeType: string, width: int, height: int, mode: string, quality: int, position: string, media: string|null, density: string|null}>>
     *
     * @throws ExceptionInterface
     */
    public function generateVariants(ImageAsset $imageAsset, array $variantConfigs): array;
}
