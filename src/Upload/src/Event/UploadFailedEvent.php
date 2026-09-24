<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when any unrecoverable error occurs while handling an upload.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class UploadFailedEvent extends Event
{
    public function __construct(
        private readonly string $uploadId,
        private readonly \Throwable $error,
    ) {
    }

    public function getUploadId(): string
    {
        return $this->uploadId;
    }

    public function getError(): \Throwable
    {
        return $this->error;
    }
}
