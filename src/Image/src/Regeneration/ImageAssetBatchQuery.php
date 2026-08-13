<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Regeneration;

final readonly class ImageAssetBatchQuery
{
    public function __construct(
        public string $profile,
        public string $storage,
        public int $limit,
        public ?string $after = null,
    ) {
        if ('' === trim($profile) || '' === trim($storage) || $limit < 1 || $limit > 1000) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException('A regeneration query requires profile, storage and a limit between 1 and 1000.');
        }
    }

    public function getProfile(): string
    {
        return $this->profile;
    }

    public function getStorage(): string
    {
        return $this->storage;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getAfter(): ?string
    {
        return $this->after;
    }
}
