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
 * A node of the minimal CSS tree that mirrors the postcss behaviors Panda relies on.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
abstract class Node
{
    public ?Container $parent = null;
    public string $before = '';

    abstract public function toString(): string;

    /**
     * The same CSS without the whitespace that only serves readability.
     */
    abstract public function toCompactString(): string;

    public function remove(): void
    {
        $this->parent?->removeChild($this);
    }

    public function next(): ?self
    {
        if (null === $this->parent) {
            return null;
        }

        return $this->parent->nodes[$this->parent->indexOf($this) + 1] ?? null;
    }

    public function replaceWith(self $node): void
    {
        if (null === $this->parent) {
            return;
        }

        $this->parent->insertBefore($this, $node);
        $this->remove();
    }

    public function __clone()
    {
        $this->parent = null;
    }
}
