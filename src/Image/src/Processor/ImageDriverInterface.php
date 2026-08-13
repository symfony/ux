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

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Image\Exception\ExceptionInterface;

/**
 * Low-level image operations implemented by a processing driver.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
#[AutoconfigureTag('ux_image.processor')]
interface ImageDriverInterface extends ImageProcessorInterface
{
    public function supports(string $driver): bool;

    /** @throws ExceptionInterface */
    public function resize(string $inputPath, string $outputPath, int $width, int $height, string $mode = 'fit', string $position = 'center'): void;

    /** @throws ExceptionInterface */
    public function convert(string $inputPath, string $outputPath, string $format, int $quality = 80): void;

    /**
     * @return array{width: int|null, height: int|null, mime: string|null, format: string|null}
     */
    public function extractMetadata(UploadedFile $file): array;
}
