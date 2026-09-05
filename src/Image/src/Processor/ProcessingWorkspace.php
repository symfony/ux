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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Image\Exception\ImageLimitExceededException;
use Symfony\UX\Image\Exception\ImageProcessingException;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\InspectedImage;
use Symfony\UX\Image\ProcessingLimits;
use Symfony\UX\Image\Storage\StoragePath;
use Symfony\UX\Image\Storage\StreamStorageInterface;

final class ProcessingWorkspace
{
    private readonly string $directory;

    public function __construct()
    {
        $this->directory = sys_get_temp_dir().'/ux-image-'.bin2hex(random_bytes(12));
        if (!mkdir($this->directory, 0o700, true) && !is_dir($this->directory)) {
            throw ImageProcessingException::processingFailed('workspace', 'Could not create the image processing workspace.');
        }
    }

    public function materialize(StreamStorageInterface $storage, ImageAsset $asset, ?ProcessingLimits $limits = null): string
    {
        $limits ??= new ProcessingLimits();
        $path = StoragePath::fromAssetPath($asset->path);
        $input = $storage->readStream($asset->storageName, $path);
        $local = $this->copyStream($input, pathinfo($path->value, \PATHINFO_EXTENSION), $limits);
        InspectedImage::fromPath($local, $limits);

        return $local;
    }

    public function materializeLocal(string $source, ?ProcessingLimits $limits = null): string
    {
        $limits ??= new ProcessingLimits();
        $input = @fopen($source, 'r');
        if (!\is_resource($input)) {
            throw ImageProcessingException::processingFailed('workspace', \sprintf('Could not read local image "%s".', $source));
        }
        $local = $this->copyStream($input, pathinfo($source, \PATHINFO_EXTENSION), $limits);
        InspectedImage::fromPath($local, $limits);

        return $local;
    }

    /**
     * @param resource $input
     */
    private function copyStream($input, string $extension, ProcessingLimits $limits): string
    {
        $local = $this->directory.'/original.'.('' !== $extension ? $extension : 'image');
        $output = fopen($local, 'w');
        if (!\is_resource($output)) {
            fclose($input);
            throw ImageProcessingException::processingFailed('workspace', 'Could not materialize the image in the processing workspace.');
        }
        try {
            $copied = stream_copy_to_stream($input, $output, $limits->maxInputBytes + 1);
            if (false === $copied) {
                throw ImageProcessingException::processingFailed('workspace', 'Could not materialize the image in the processing workspace.');
            }
            if ($copied > $limits->maxInputBytes) {
                throw ImageLimitExceededException::inputBytes($copied, $limits->maxInputBytes);
            }
        } finally {
            fclose($input);
            fclose($output);
        }

        return $local;
    }

    public function path(string $filename): string
    {
        return $this->directory.'/'.new StoragePath($filename)->value;
    }

    public function cleanup(): void
    {
        new Filesystem()->remove($this->directory);
    }

    public function __destruct()
    {
        $this->cleanup();
    }
}
