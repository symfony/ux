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

final readonly class ImageAssetBatch
{
    /** @param list<ImageAssetReference> $items */
    public function __construct(
        public array $items,
        public ?string $nextCursor,
    ) {
        if ('' === $nextCursor) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException('A batch cursor cannot be empty.');
        }
        if ([] === $items && null !== $nextCursor) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException('An empty image asset batch cannot expose a next cursor.');
        }
        $ids = [];
        $cursors = [];
        foreach ($items as $item) {
            if (isset($ids[$item->id]) || isset($cursors[$item->cursor])) {
                throw new \Symfony\UX\Image\Exception\InvalidArgumentException('A batch cannot contain duplicate asset ids or cursors.');
            }
            $ids[$item->id] = true;
            $cursors[$item->cursor] = true;
        }
    }

    /**
     * @return list<ImageAssetReference>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getNextCursor(): ?string
    {
        return $this->nextCursor;
    }
}
