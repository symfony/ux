<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Engine\Css;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Declaration extends Node
{
    public function __construct(
        public string $property,
        public string $value,
        public bool $important = false,
    ) {
    }

    public function equals(self $other): bool
    {
        return $this->important === $other->important
            && $this->property === $other->property
            && $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->property.': '.$this->value.($this->important ? ' !important' : '').';';
    }

    public function toCompactString(): string
    {
        return $this->property.':'.$this->value.($this->important ? '!important' : '');
    }
}
