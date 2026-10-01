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
 * Position a cursor page starts from.
 *
 * The values are adapter-private: a field-based adapter stores one per ordered
 * field, an adapter wrapping a source that mints its own cursors stores that
 * cursor as a single opaque token.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class CursorBoundary
{
    /**
     * @param list<int|string|float> $values
     */
    public function __construct(
        public readonly array $values,
        public readonly bool $forward = true,
    ) {
    }

    /**
     * @return list<int|string|float>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function pointsForward(): bool
    {
        return $this->forward;
    }
}
