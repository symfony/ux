<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\EventListener;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\MimeTypesInterface;
use Symfony\UX\Upload\Event\UploadAssembledEvent;
use Symfony\UX\Upload\Exception\ValidationException;
use Symfony\UX\Upload\UploaderInterface;

/**
 * Validates completed uploads against size and MIME type constraints.
 *
 * Runs at high priority so validation happens before any other listeners
 * process the completed upload. Throws ValidationException to abort the
 * upload pipeline when constraints are violated.
 *
 * When a named uploader is configured, per-uploader validation constraints
 * (max_size, allowed_types) are read from the uploader's config. Falls back
 * to global defaults when no uploader is specified.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class FileValidationListener
{
    public const int PRIORITY = 255;

    /**
     * @param list<string> $allowedTypes MIME types to accept (e.g. ["image/jpeg", "image/*", "application/pdf"])
     * @param int          $maxSize      Maximum file size in bytes (0 = no limit)
     */
    public function __construct(
        private MimeTypesInterface $mimeTypes,
        private ?ContainerInterface $uploaders = null,
        private array $allowedTypes = [],
        private int $maxSize = 0,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function __invoke(UploadAssembledEvent $event): void
    {
        [$maxSize, $allowedTypes] = $this->resolveConstraints($event);

        $upload = $event->getUpload();

        if ($maxSize > 0 && $upload->size > $maxSize) {
            throw new ValidationException(\sprintf('File size %d bytes exceeds maximum allowed size of %d bytes.', $upload->size, $maxSize));
        }

        // Detect actual MIME type from file content (not client-declared)
        $detectedMimeType = null;
        try {
            $content = $upload->openStream();
            $chunk = fread($content, 2048);
            fclose($content);

            if (false !== $chunk && '' !== $chunk) {
                $finfo = new \finfo(\FILEINFO_MIME_TYPE);
                $detected = $finfo->buffer($chunk);
                if (false !== $detected) {
                    $detectedMimeType = $detected;
                }
            }
        } catch (\Throwable $exception) {
            $this->logger?->error('Unable to inspect the assembled upload MIME type.', [
                'exception' => $exception,
                'uploadId' => $upload->id,
            ]);

            throw new ValidationException('Unable to inspect the assembled file content.', 0, $exception);
        }

        if (null === $detectedMimeType) {
            throw new ValidationException('Unable to determine the assembled file MIME type.');
        }

        // Log when detected MIME differs from client-declared (potential spoofing)
        if ($detectedMimeType !== $upload->mimeType) {
            $this->logger?->warning('Upload MIME type mismatch: client declared "{declared}", server detected "{detected}" for file "{filename}".', [
                'declared' => $upload->mimeType,
                'detected' => $detectedMimeType,
                'filename' => $upload->originalName,
            ]);
        }

        $candidates = [$detectedMimeType];
        if ('text/plain' === $detectedMimeType) {
            $extension = strtolower(pathinfo($upload->originalName, \PATHINFO_EXTENSION));
            foreach ('' !== $extension ? $this->mimeTypes->getMimeTypes($extension) : [] as $extensionMimeType) {
                if (str_starts_with($extensionMimeType, 'text/')) {
                    $candidates[] = $extensionMimeType;
                }
            }
        }
        $candidates = array_values(array_unique($candidates));

        if ([] === $allowedTypes) {
            $mimeType = \in_array($upload->mimeType, $candidates, true) ? $upload->mimeType : $detectedMimeType;
            $event->setUpload($upload->withMimeType($mimeType));

            return;
        }

        foreach ($candidates as $candidate) {
            if ($this->isAllowedType($candidate, $allowedTypes)) {
                $event->setUpload($upload->withMimeType($candidate));

                return;
            }
        }

        throw new ValidationException(\sprintf('MIME type "%s" is not allowed. Allowed types: %s.', $detectedMimeType, implode(', ', $allowedTypes)));
    }

    /**
     * @param list<string> $allowedTypes
     */
    private function isAllowedType(string $mimeType, array $allowedTypes): bool
    {
        foreach ($allowedTypes as $allowedType) {
            if ($mimeType === $allowedType
                || (str_ends_with($allowedType, '/*') && str_starts_with($mimeType, substr($allowedType, 0, -1)))
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{int, list<string>}
     */
    private function resolveConstraints(UploadAssembledEvent $event): array
    {
        $metadata = $event->getMetadata();
        $policyMaxSize = $metadata['policyMaxSize'] ?? null;
        $policyAllowedTypes = $metadata['policyAllowedTypes'] ?? null;
        if (\is_int($policyMaxSize) && \is_array($policyAllowedTypes) && array_is_list($policyAllowedTypes)) {
            /* @var list<string> $policyAllowedTypes */
            return [$policyMaxSize, $policyAllowedTypes];
        }

        /** @var string|null $uploaderName */
        $uploaderName = $metadata['uploader'] ?? null;

        if (null !== $uploaderName && 'default' !== $uploaderName && null !== $this->uploaders && $this->uploaders->has($uploaderName)) {
            $uploader = $this->uploaders->get($uploaderName);
            if (!$uploader instanceof UploaderInterface) {
                throw new \LogicException(\sprintf('Uploader service "%s" must implement "%s".', $uploaderName, UploaderInterface::class));
            }
            $config = $uploader->getConfig();

            return [$config['max_size'], $config['allowed_types']];
        }

        return [$this->maxSize, $this->allowedTypes];
    }
}
