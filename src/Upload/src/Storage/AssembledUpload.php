<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Storage;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class AssembledUpload
{
    public function __construct(
        public readonly string $path,
        public readonly int $size,
        public readonly ?string $hash,
    ) {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }
}
