<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Engine;

/**
 * One atomic style, as Panda's encoder hashes it: the value is kept as the string JavaScript would print.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class StyleEntry
{
    /**
     * @param list<string> $conditions
     */
    public function __construct(
        public readonly string $property,
        public readonly string $value,
        public readonly array $conditions = [],
    ) {
    }

    public function hash(): string
    {
        $hash = $this->property.']___[value:'.$this->value;
        if ([] !== $this->conditions) {
            $hash .= ']___[cond:'.implode('<___>', $this->conditions);
        }

        return $hash;
    }
}
