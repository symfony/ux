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
use Symfony\UX\Image\Exception\RuntimeException;
use Symfony\UX\Image\ImageAsset;

/**
 * Chain processor that delegates to the first supporting processor.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ChainImageProcessor implements ImageDriverInterface
{
    /**
     * @param iterable<ImageDriverInterface> $processors
     */
    public function __construct(
        private readonly iterable $processors,
        private readonly string $defaultDriver = 'gd',
    ) {
    }

    public function process(UploadedFile $file, ?string $profile = null, string $storage = 'default_public'): ImageAsset
    {
        return $this->getProcessor()->process($file, $profile, $storage);
    }

    /**
     * @param array<string, mixed> $variantConfigs
     *
     * @return array<string, list<array{name: string, path: string, format: string, mimeType: string, width: int, height: int, mode: string, quality: int, position: string, media: string|null, density: string|null}>>
     */
    public function generateVariants(ImageAsset $imageAsset, array $variantConfigs): array
    {
        return $this->getProcessor()->generateVariants($imageAsset, $variantConfigs);
    }

    public function resize(string $inputPath, string $outputPath, int $width, int $height, string $mode = 'fit', string $position = 'center'): void
    {
        $this->getProcessor()->resize($inputPath, $outputPath, $width, $height, $mode, $position);
    }

    public function convert(string $inputPath, string $outputPath, string $format, int $quality = 80): void
    {
        $this->getProcessor()->convert($inputPath, $outputPath, $format, $quality);
    }

    /**
     * @return array{width: int|null, height: int|null, mime: string|null, format: string|null}
     */
    public function extractMetadata(UploadedFile $file): array
    {
        return $this->getProcessor()->extractMetadata($file);
    }

    public function supports(string $driver): bool
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($driver)) {
                return true;
            }
        }

        return false;
    }

    private function getProcessor(): ImageDriverInterface
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($this->defaultDriver)) {
                return $processor;
            }
        }

        throw new RuntimeException(\sprintf('No image processor found for driver "%s".', $this->defaultDriver));
    }
}
