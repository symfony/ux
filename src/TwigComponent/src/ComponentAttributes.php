<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class ComponentAttributes
{
    /**
     * @param array<string, string> $attributes
     */
    public function __construct(public array $attributes)
    {
    }

    public function __toString(): string
    {
        return \array_reduce(
            \array_keys($this->attributes),
            fn(string $carry, string $key) => \sprintf('%s %s="%s"', $carry, $key, $this->attributes[$key]),
            ''
        );
    }

    public function merge(array $with): self
    {
        foreach ($this->attributes as $key => $value) {
            $with[$key] = isset($with[$key]) ? "{$with[$key]} {$value}" : $value;
        }

        return new self($with);
    }

    public function only(string ...$keys): self
    {
        $attributes = [];

        foreach ($this->attributes as $key => $value) {
            if (in_array($key, $keys, true)) {
                $attributes[$key] = $value;
            }
        }

        return new self($attributes);
    }

    public function without(string ...$keys): self
    {
        $clone = clone $this;

        foreach ($keys as $key) {
            unset($clone->attributes[$key]);
        }

        return $clone;
    }

    /**
     * @param array<string, string> $attributes
     */
    public function defaults(array $attributes): self
    {
        $clone = $this;

        foreach ($attributes as $attribute => $value) {
            $clone->attributes[$attribute] = $clone->attributes[$attribute] ?? $value;
        }

        return $clone;
    }

    public function default(string $attribute, string $value): self
    {
        return $this->defaults([$attribute => $value]);
    }
}
