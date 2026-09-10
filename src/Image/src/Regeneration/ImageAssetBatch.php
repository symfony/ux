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

use Symfony\UX\Image\Exception\InvalidArgumentException;

final class ImageAssetBatch
{
    /** @param list<ImageAssetReference> $items */
    public function __construct(
        public readonly array $items,
        public readonly ?string $nextCursor,
    ) {
        if ('' === $nextCursor) {
            throw new InvalidArgumentException('A batch cursor cannot be empty.');
        }
        if ([] === $items && null !== $nextCursor) {
            throw new InvalidArgumentException('An empty image asset batch cannot expose a next cursor.');
        }
        $ids = [];
        $cursors = [];
        foreach ($items as $item) {
            if (isset($ids[$item->id]) || isset($cursors[$item->cursor])) {
                throw new InvalidArgumentException('A batch cannot contain duplicate asset ids or cursors.');
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
