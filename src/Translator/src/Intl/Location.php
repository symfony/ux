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
 * Adapted from https://github.com/formatjs/formatjs/blob/590f1f81b26934c6dc7a55fff938df5436c6f158/packages/icu-messageformat-parser/types.ts#L58-L61.
 *
 * @experimental
 */
final class Location
{
    public Position $start;
    public Position $end;

    public function __construct(Position $start, Position $end)
    {
        $this->start = $start;
        $this->end = $end;
    }
}
