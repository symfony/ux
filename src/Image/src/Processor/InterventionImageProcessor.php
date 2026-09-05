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

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ImageManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Image\Async\ImageProcessingDispatcherInterface;
use Symfony\UX\Image\Exception\ImageLimitExceededException;
use Symfony\UX\Image\Exception\ImageProcessingException;
use Symfony\UX\Image\Exception\UnknownImageProfileException;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\ImageSource;
use Symfony\UX\Image\ProcessingLimits;
use Symfony\UX\Image\Profile\ProcessingMode;
use Symfony\UX\Image\Storage\ImageWriteSession;
use Symfony\UX\Image\Storage\StorageInterface;
use Symfony\UX\Image\Storage\StoragePath;
use Symfony\UX\Image\Storage\StreamStorageInterface;
use Symfony\UX\Image\Svg\RejectSvgPolicy;
use Symfony\UX\Image\Svg\SvgPolicyInterface;
use Symfony\UX\Image\Transformation\FocalPoint;
use Symfony\UX\Image\Transformation\ResizeGeometryCalculator;
use Symfony\UX\Image\Transformation\ResizeMode;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class InterventionImageProcessor implements ImageDriverInterface
{
    /** @param array<string, array<string, mixed>> $profiles */
    public function __construct(
        private readonly StorageInterface $storageManager,
        private readonly array $profiles,
        private readonly ImageInspectorInterface $imageInspector,
        private readonly ?ImageManagerInterface $imageManager = null,
        private readonly ?SvgPolicyInterface $svgPolicy = null,
        private readonly ?ResizeGeometryCalculator $geometryCalculator = null,
        private readonly ?ImageProcessingDispatcherInterface $asyncDispatcher = null,
        private readonly ?ProcessingLimits $limits = null,
    ) {
    }

    public function process(UploadedFile $file, ?string $profile = null, string $storage = 'default_public'): ImageAsset
    {
        $metadata = $this->extractMetadata($file);
        if ('image/svg+xml' === ($metadata['mime'] ?? null)) {
            $file = ($this->svgPolicy ?? new RejectSvgPolicy())->process($file);
            $metadata = $this->extractMetadata($file);
            if ('image/svg+xml' === ($metadata['mime'] ?? null)) {
                throw ImageProcessingException::processingFailed('svg policy', 'The policy must return a safe raster image.');
            }
        }
        $inspection = $this->imageInspector->inspectImage($file, $this->limits ?? new ProcessingLimits());

        if (null !== $profile && !isset($this->profiles[$profile])) {
            throw UnknownImageProfileException::create($profile, array_keys($this->profiles));
        }
        $profile ??= isset($this->profiles['responsive_default']) ? 'responsive_default' : null;
        $profileConfig = null !== $profile ? $this->profiles[$profile] : null;
        $directory = $profileConfig['directory'] ?? null;
        $processing = ProcessingMode::fromProfile($profileConfig);
        $sourceWorkspace = null;
        $sourcePath = null;
        if (ProcessingMode::Immediate === $processing && $this->storageManager instanceof StreamStorageInterface) {
            $sourceWorkspace = new ProcessingWorkspace();
            $sourcePath = $sourceWorkspace->materializeLocal(
                $file->getRealPath() ?: $file->getPathname(),
                $this->limits ?? new ProcessingLimits(),
            );
        }

        try {
            // Read metadata before storing: store() may move the uploaded file, after
            // which the source path no longer resolves for inspection.
            $storedPath = $this->storageManager->store($file, $storage, \is_string($directory) ? $directory : null);
            $asset = new ImageAsset(
                storageName: $storage,
                path: $storedPath,
                originalFilename: $file->getClientOriginalName(),
                mimeType: $inspection->mimeType,
                width: $inspection->width,
                height: $inspection->height,
                profile: $profile,
            );

            try {
                if (ProcessingMode::Async === $processing) {
                    if (null === $profile || null === $this->asyncDispatcher) {
                        throw ImageProcessingException::processingFailed('async dispatch', 'An async profile requires ImageProcessingDispatcherInterface.');
                    }
                    $this->asyncDispatcher->dispatch($asset, $profile);
                    $variants = [];
                    $profileRevision = null;
                } else {
                    $variants = ProcessingMode::Immediate === $processing ? $this->generateVariantsFromSource($asset, $profileConfig ?? [], $sourcePath) : [];
                    $profileRevision = ProcessingMode::Immediate === $processing && null !== $profileConfig
                        ? hash('sha256', json_encode($profileConfig, \JSON_THROW_ON_ERROR))
                        : null;
                }
            } catch (\Throwable $e) {
                $this->storageManager->delete($asset);
                throw $e;
            }

            return new ImageAsset(
                storageName: $asset->storageName,
                path: $asset->path,
                originalFilename: $asset->originalFilename,
                mimeType: $asset->mimeType,
                width: $inspection->width,
                height: $inspection->height,
                variants: $variants,
                profile: $profile,
                profileRevision: $profileRevision,
            );
        } finally {
            $sourceWorkspace?->cleanup();
        }
    }

    /**
     * @param array<string, mixed> $variantConfigs
     *
     * @return array<string, list<array{name: string, path: string, format: string, mimeType: string, width: int, height: int, mode: string, quality: int, position: string, media: string|null, density: string|null}>>
     */
    public function generateVariants(ImageAsset $imageAsset, array $variantConfigs): array
    {
        return $this->generateVariantsFromSource($imageAsset, $variantConfigs);
    }

    /**
     * @param array<string, mixed> $variantConfigs
     *
     * @return array<string, list<array{name: string, path: string, format: string, mimeType: string, width: int, height: int, mode: string, quality: int, position: string, media: string|null, density: string|null}>>
     */
    private function generateVariantsFromSource(ImageAsset $imageAsset, array $variantConfigs, ?string $sourcePath = null): array
    {
        if (!isset($variantConfigs['variants']) || !\is_array($variantConfigs['variants'])
            || !isset($variantConfigs['formats']) || !\is_array($variantConfigs['formats'])) {
            return [];
        }

        $variants = [];
        $workspace = new ProcessingWorkspace();
        $streamStorage = $this->storageManager instanceof StreamStorageInterface ? $this->storageManager : null;
        $limits = $this->limits ?? new ProcessingLimits();
        /** @var array<string, mixed> $configuredVariants */
        $configuredVariants = $variantConfigs['variants'];
        /** @var array<array-key, mixed> $configuredFormats */
        $configuredFormats = $variantConfigs['formats'];
        try {
            $originalPath = null !== $sourcePath
                ? $sourcePath
                : (null !== $streamStorage
                    ? $workspace->materialize($streamStorage, $imageAsset, $limits)
                    : $this->storageManager->getFilePath($imageAsset));
            $input = \Symfony\UX\Image\InspectedImage::fromPath($originalPath, $limits);
            $plan = new VariantProcessingPlanner(
                $limits,
                $this->geometryCalculator ?? new ResizeGeometryCalculator(),
            )->plan($input, $configuredVariants, $configuredFormats);
        } catch (\Throwable $e) {
            $workspace->cleanup();
            throw $e;
        }
        if (0 === $plan->artifactCount) {
            $workspace->cleanup();

            return [];
        }
        $targetDir = null === $streamStorage ? \dirname($this->storageManager->getFilePath($imageAsset)) : '';
        $assetPath = StoragePath::fromAssetPath($imageAsset->path)->value;
        $separatorPosition = strrpos($assetPath, '/');
        $relativeDir = false === $separatorPosition ? '' : substr($assetPath, 0, $separatorPosition);
        $filename = false === $separatorPosition ? $assetPath : substr($assetPath, $separatorPosition + 1);
        $baseName = pathinfo($filename, \PATHINFO_FILENAME) ?: 'variant';
        $generation = bin2hex(random_bytes(12));
        $writeSession = null !== $streamStorage ? new ImageWriteSession($streamStorage, $imageAsset->storageName) : null;
        $localPublications = [];
        $index = 0;
        $outputPixels = 0;

        try {
            foreach ($plan->variants as $plannedVariant) {
                $resizedPath = $workspace->path(\sprintf('resized-%d.%s', ++$index, pathinfo($originalPath, \PATHINFO_EXTENSION) ?: 'jpeg'));
                $this->resize(
                    $originalPath,
                    $resizedPath,
                    $plannedVariant->width,
                    $plannedVariant->height,
                    $plannedVariant->mode,
                    $plannedVariant->position,
                );
                if (null === $this->imageManager) {
                    throw ImageProcessingException::processingFailed('encode', 'Intervention ImageManager is required for format conversion.');
                }
                try {
                    /** @var ImageInterface $encodingImage */
                    $encodingImage = $this->imageManager->read($resizedPath);
                } catch (\Throwable $e) {
                    throw ImageProcessingException::processingFailed('encode', $e->getMessage());
                }

                foreach ($plan->formats as $format) {
                    $variantFilename = \sprintf('%s_%s_%s.%s', $baseName, $generation, $plannedVariant->name, $format);
                    $storagePath = new StoragePath(('' !== $relativeDir ? $relativeDir.'/' : '').$variantFilename);
                    $encodedPath = $workspace->path(\sprintf('encoded-%d-%s.%s', $index, $format, $format));

                    try {
                        $encodingImage->save($encodedPath, $plannedVariant->quality, $format);
                    } catch (\Throwable $e) {
                        throw ImageProcessingException::processingFailed('encode', $e->getMessage());
                    }
                    $inspection = \Symfony\UX\Image\InspectedImage::fromPath($encodedPath, $limits);
                    if ($inspection->format !== $format) {
                        throw ImageProcessingException::processingFailed('encode', \sprintf('Expected %s, got %s.', $format, $inspection->format));
                    }
                    $outputPixels += $inspection->pixelCount();
                    $outputPixelLimit = $limits->maxOutputPixels;
                    if ($outputPixels > $outputPixelLimit) {
                        throw ImageLimitExceededException::outputPixels($outputPixels, $outputPixelLimit);
                    }
                    if (null !== $writeSession) {
                        $writeSession->stage($storagePath, $encodedPath);
                    } else {
                        $localPublications[] = [$encodedPath, $targetDir.'/'.$variantFilename];
                    }

                    $variants[$format][] = ImageSource::generated(
                        name: $plannedVariant->name,
                        path: $storagePath,
                        format: $format,
                        mimeType: $inspection->mimeType,
                        width: $inspection->width,
                        height: $inspection->height,
                        media: $plannedVariant->media,
                        density: $plannedVariant->density,
                        mode: $plannedVariant->mode,
                        quality: $plannedVariant->quality,
                        position: $plannedVariant->position,
                    )->toGeneratedArray();
                }
                unset($encodingImage);
            }
            if (null !== $writeSession) {
                $writeSession->commit();
            } else {
                foreach ($localPublications as [$stagedPath, $absolutePath]) {
                    $this->filesystem()->rename($stagedPath, $absolutePath, false);
                }
            }
        } catch (\Throwable $e) {
            $writeSession?->rollback();
            throw $e;
        } finally {
            $workspace->cleanup();
        }

        return $variants;
    }

    public function resize(string $inputPath, string $outputPath, int $width, int $height, string $mode = 'fit', string $position = 'center'): void
    {
        if (null === $this->imageManager) {
            $this->filesystem()->mkdir(\dirname($outputPath));

            if (!copy($inputPath, $outputPath)) {
                throw ImageProcessingException::processingFailed('resize', \sprintf('Could not copy "%s" to "%s".', $inputPath, $outputPath));
            }

            return;
        }

        try {
            $input = \Symfony\UX\Image\InspectedImage::fromPath($inputPath, $this->limits ?? new ProcessingLimits());
            $geometry = ($this->geometryCalculator ?? new ResizeGeometryCalculator())->calculate(
                $input->width,
                $input->height,
                $width,
                $height,
                ResizeMode::from($mode),
                FocalPoint::fromString($position),
            );
            $this->assertOutputAllocation($geometry->canvasWidth, $geometry->canvasHeight);
            /** @var ImageInterface $image */
            $image = $this->imageManager->read($inputPath);
            if ('crop' === $mode) {
                $image->crop($geometry->sourceWidth, $geometry->sourceHeight, $geometry->sourceX, $geometry->sourceY);
            }
            $image->scaleDown(width: $geometry->destinationWidth, height: $geometry->destinationHeight);
            if ('fill' === $mode) {
                $image->resizeCanvas($geometry->canvasWidth, $geometry->canvasHeight, 'transparent', 'center');
            }

            $this->filesystem()->mkdir(\dirname($outputPath));
            $image->save($outputPath);
        } catch (\Throwable $e) {
            throw ImageProcessingException::processingFailed('resize', $e->getMessage());
        }
    }

    public function convert(string $inputPath, string $outputPath, string $format, int $quality = 80): void
    {
        if (null === $this->imageManager) {
            throw ImageProcessingException::processingFailed('convert', 'Intervention ImageManager is required for format conversion.');
        }

        try {
            /** @var ImageInterface $image */
            $image = $this->imageManager->read($inputPath);
            $image->save($outputPath, $quality, $format);
        } catch (\Throwable $e) {
            throw ImageProcessingException::processingFailed('convert', $e->getMessage());
        }
    }

    /**
     * @return array{width: int|null, height: int|null, mime: string|null, format: string|null}
     */
    public function extractMetadata(UploadedFile $file): array
    {
        return $this->imageInspector->inspect($file);
    }

    public function supports(string $driver): bool
    {
        return 'intervention' === $driver || 'imagick' === $driver || 'vips' === $driver;
    }

    private function filesystem(): Filesystem
    {
        return new Filesystem();
    }

    private function assertOutputAllocation(int $width, int $height): void
    {
        ($this->limits ?? new ProcessingLimits())->assertOutputAllocation($width, $height);
    }
}
