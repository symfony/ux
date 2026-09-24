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
use Symfony\UX\Upload\Upload\UploadedFile;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class UploadAssembledEvent extends Event
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private UploadedFile $upload,
        private readonly array $metadata = [],
    ) {
    }

    public function getUpload(): UploadedFile
    {
        return $this->upload;
    }

    public function setUpload(UploadedFile $upload): void
    {
        $this->upload = $upload;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
