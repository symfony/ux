<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Renderer;

/**
 * A list of already encoded JSON objects, passed as is to StimulusAttributes instead of being encoded again.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class JsonList implements \Stringable
{
    /**
     * @param list<string> $items
     */
    public function __construct(
        private readonly array $items,
    ) {
    }

    public function __toString(): string
    {
        return '['.implode(',', $this->items).']';
    }
}
