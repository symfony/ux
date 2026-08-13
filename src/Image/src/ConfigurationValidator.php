<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image;

use Symfony\UX\Image\Processor\ImageDriverInterface;
use Symfony\UX\Image\Storage\StorageInterface;
use Symfony\UX\Image\Storage\StoragePath;
use Symfony\UX\Image\Storage\StreamStorageInterface;

/**
 * Performs runtime capability probes used by ux:image:validate.
 *
 * @internal
 */
final readonly class ConfigurationValidator
{
    private const PROBE_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAIAAAD91JpzAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAFklEQVQImWNUSFjAwMDAxMDAwMDAAAANKgEkOVXj2AAAAABJRU5ErkJggg==';

    /**
     * @param array<string, array<string, mixed>> $storages
     * @param array<string, array<string, mixed>> $profiles
     */
    public function __construct(
        private ImageDriverInterface $imageDriver,
        private StorageInterface $storage,
        private ?string $driver,
        private array $storages,
        private array $profiles,
    ) {
    }

    /**
     * @return list<string>
     */
    public function validateDriver(): array
    {
        if (null === $this->driver) {
            return [];
        }

        if (!$this->imageDriver->supports($this->driver)) {
            return [\sprintf('The configured processor does not support driver "%s".', $this->driver)];
        }

        $formats = [];
        foreach ($this->profiles as $profile) {
            foreach (\is_array($profile['formats'] ?? null) ? $profile['formats'] : [] as $format) {
                if (\is_string($format)) {
                    $formats['jpg' === strtolower($format) ? 'jpeg' : strtolower($format)] = true;
                }
            }
        }

        if ([] === $formats) {
            return [];
        }

        $input = tempnam(sys_get_temp_dir(), 'ux_image_validate_');
        if (false === $input || false === file_put_contents($input, base64_decode(self::PROBE_PNG, true))) {
            return ['Could not create the temporary image used to validate driver codecs.'];
        }

        $errors = [];
        try {
            foreach (array_keys($formats) as $format) {
                $output = $input.'.'.$format;
                try {
                    $this->imageDriver->convert($input, $output, $format);
                    $inspected = InspectedImage::fromPath($output);
                    if ($format !== $inspected->format) {
                        $errors[] = \sprintf('Driver "%s" produced "%s" while validating the "%s" codec.', $this->driver, $inspected->format, $format);
                    }
                } catch (\Throwable $e) {
                    $errors[] = \sprintf('Driver "%s" cannot encode "%s": %s', $this->driver, $format, $e->getMessage());
                } finally {
                    if (is_file($output)) {
                        @unlink($output);
                    }
                }
            }
        } finally {
            @unlink($input);
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    public function validateStorages(): array
    {
        if (!$this->storage instanceof StreamStorageInterface) {
            return [\sprintf('The configured storage must implement %s so image variants can be read and published.', StreamStorageInterface::class)];
        }

        $errors = [];
        foreach (array_unique(['default_public', ...array_keys($this->storages)]) as $storageName) {
            $path = new StoragePath('.ux-image-validation/'.bin2hex(random_bytes(12)).'.probe');
            $payload = 'ux-image-storage-probe';
            $writeStream = fopen('php://temp', 'w+');
            if (!\is_resource($writeStream)) {
                $errors[] = \sprintf('Storage "%s" could not be validated because a probe stream could not be created.', $storageName);
                continue;
            }
            fwrite($writeStream, $payload);
            rewind($writeStream);

            try {
                $this->storage->writeStream($storageName, $path, $writeStream);
                $readStream = $this->storage->readStream($storageName, $path);
                try {
                    if ($payload !== stream_get_contents($readStream)) {
                        $errors[] = \sprintf('Storage "%s" returned different bytes for a validation probe.', $storageName);
                    }
                } finally {
                    fclose($readStream);
                }
            } catch (\Throwable $e) {
                $errors[] = \sprintf('Storage "%s" failed its write/read/delete capability probe: %s', $storageName, $e->getMessage());
            } finally {
                fclose($writeStream);
                try {
                    $this->storage->deletePath($storageName, $path);
                } catch (\Throwable $e) {
                    $errors[] = \sprintf('Storage "%s" could not remove its validation probe: %s', $storageName, $e->getMessage());
                }
            }
        }

        return $errors;
    }
}
