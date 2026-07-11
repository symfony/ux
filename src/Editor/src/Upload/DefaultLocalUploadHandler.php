<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Upload;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Editor\Exception\Upload\UnsupportedFileException;
use Symfony\UX\Editor\Exception\Upload\UploadHandlerException;

final class DefaultLocalUploadHandler implements EditorUploadHandlerInterface
{
    /**
     * @param list<string> $allowedMimes
     */
    public function __construct(
        private readonly string $targetDir,
        private readonly string $publicUrlPrefix = '/uploads',
        private readonly array $allowedMimes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'],
        private readonly int $maxBytes = 5_000_000,
    ) {
    }

    public function handle(UploadedFile $file, array $context = []): array
    {
        $mime = $file->getClientMimeType();
        if (!\in_array($mime, $this->allowedMimes, true)) {
            throw new UnsupportedFileException('Mime type not allowed: '.($mime ?? 'unknown'));
        }
        $size = $file->getSize();
        if (false === $size || $size > $this->maxBytes) {
            throw new UnsupportedFileException('File too large: '.($size ?: '?').' > '.$this->maxBytes);
        }
        $clientName = $file->getClientOriginalName();
        $ext = ($clientName && str_contains($clientName, '.'))
            ? strtolower(pathinfo($clientName, \PATHINFO_EXTENSION))
            : 'bin';
        $name = bin2hex(random_bytes(8)).'.'.$ext;
        try {
            $file->move($this->targetDir, $name);
        } catch (\Throwable $e) {
            throw new UploadHandlerException('Could not store upload.', 0, $e);
        }

        return [
            'url' => rtrim($this->publicUrlPrefix, '/').'/'.$name,
            'size' => $size,
            'type' => $mime,
        ];
    }
}
