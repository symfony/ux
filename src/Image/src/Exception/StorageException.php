<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Exception;

/**
 * Exception thrown when storage operations fail.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class StorageException extends RuntimeException
{
    public static function storageNotFound(string $storageName): self
    {
        return new self(\sprintf('Storage "%s" not found. Check your ux_image configuration.', $storageName));
    }

    public static function uploadFailed(string $filename, string $reason = ''): self
    {
        $message = \sprintf('Failed to upload file "%s"', $filename);
        if ($reason) {
            $message .= ': '.$reason;
        }

        return new self($message);
    }

    public static function deletionFailed(string $path, string $reason = ''): self
    {
        $message = \sprintf('Failed to delete file "%s"', $path);
        if ($reason) {
            $message .= ': '.$reason;
        }

        return new self($message);
    }

    public static function readFailed(string $path, string $reason = ''): self
    {
        $message = \sprintf('Failed to read file "%s"', $path);
        if ($reason) {
            $message .= ': '.$reason;
        }

        return new self($message);
    }

    public static function writeFailed(string $path, string $reason = ''): self
    {
        $message = \sprintf('Failed to write file "%s"', $path);
        if ($reason) {
            $message .= ': '.$reason;
        }

        return new self($message);
    }
}
