<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Upload;

use Symfony\UX\Upload\Exception\UploadException;
use Symfony\UX\Upload\Storage\StorageInterface;

/**
 * Internal byte access for a completed temporary upload.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class CompletedUploadAccess
{
    public function __construct(private StorageInterface $storage)
    {
    }

    /**
     * @return resource
     */
    public function openStream(string $path)
    {
        $content = $this->storage->read($path);
        if (\is_resource($content)) {
            return $content;
        }
        if (!\is_string($content)) {
            throw new UploadException(\sprintf('Temporary storage returned unsupported content of type %s.', get_debug_type($content)));
        }

        $stream = fopen('php://temp', 'w+');
        if (false === $stream) {
            throw new UploadException('Unable to create a temporary upload stream.');
        }
        try {
            if (\strlen($content) !== fwrite($stream, $content)) {
                throw new UploadException('Unable to write the temporary upload stream.');
            }
            rewind($stream);

            return $stream;
        } catch (\Throwable $e) {
            fclose($stream);
            throw $e;
        }
    }

    public function delete(string $uploadId, string $path): void
    {
        $this->storage->delete($path);
        $this->storage->abort($uploadId);
    }
}
