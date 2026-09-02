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
 * @author Simon André <smn.andre@gmail.com>
 */
interface CursorCodecInterface
{
    /**
     * @param list<int|string|float> $values
     */
    public function encode(array $values, bool $pointsForward, string $orderFingerprint, string $context): string;

    /**
     * @return array{values: list<int|string|float>, forward: bool}
     */
    public function decode(string $cursor, string $orderFingerprint, string $context): array;
}
