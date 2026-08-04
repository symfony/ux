<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Cursor;

/**
 * @template-covariant T
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class CursorSlice
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly ?CursorBoundary $next,
        public readonly ?CursorBoundary $previous,
        public readonly bool $hasNext,
    ) {
    }

    /**
     * @return list<T>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getNextBoundary(): ?CursorBoundary
    {
        return $this->next;
    }

    public function getPreviousBoundary(): ?CursorBoundary
    {
        return $this->previous;
    }

    public function hasNext(): bool
    {
        return $this->hasNext;
    }
}
