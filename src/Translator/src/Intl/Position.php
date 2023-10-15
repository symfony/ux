<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Intl;

/**
 * Adapted from https://github.com/formatjs/formatjs/blob/590f1f81b26934c6dc7a55fff938df5436c6f158/packages/icu-messageformat-parser/types.ts#L53-L57.
 *
 * @experimental
 */
final class Position
{
    /** Offset in terms of UTF-16 *code unit*. */
    public int $offset;
    public int $line;
    /** Column offset in terms of unicode *code point*. */
    public int $column;

    public function __construct(int $offset, int $line, int $column)
    {
        $this->offset = $offset;
        $this->line = $line;
        $this->column = $column;
    }
}
