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

use Symfony\WebpackEncoreBundle\Dto\AbstractStimulusDto;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 */
final class ComponentAttributes
{
    /**
     * @internal
     *
     * @param array<string, string> $attributes
     */
    public function __construct(private array $attributes)
    {
    }

    public function __toString(): string
    {
        return array_reduce(
            array_keys($this->attributes),
            function (string $carry, string $key) {
                if (null === $this->attributes[$key]) {
                    return "{$carry} {$key}";
                }

                return sprintf('%s %s="%s"', $carry, $key, $this->attributes[$key]);
            },
            ''
        );
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->attributes;
    }

    /**
     * Set default attributes. These are used if they are not already
     * defined. "class" is special, these defaults are prepended to
     * the existing "class" attribute (if available).
     */
    public function defaults(array $attributes): self
    {
        foreach ($this->attributes as $key => $value) {
            $attributes[$key] = isset($attributes[$key]) && 'class' === $key ? "{$attributes[$key]} {$value}" : $value;
        }

        return new self($attributes);
    }

    /**
     * Extract only these attributes.
     */
    public function only(string ...$keys): self
    {
        $attributes = [];

        foreach ($this->attributes as $key => $value) {
            if (\in_array($key, $keys, true)) {
                $attributes[$key] = $value;
            }
        }

        return new self($attributes);
    }

    /**
     * Extract all but these attributes.
     */
    public function without(string ...$keys): self
    {
        $clone = clone $this;

        foreach ($keys as $key) {
            unset($clone->attributes[$key]);
        }

        return $clone;
    }

    /**
     * @param array<string, string>|AbstractStimulusDto $attributes
     */
    public function append(array|AbstractStimulusDto $attributes): self
    {
        return $this->mergeAttribute($attributes);
    }

    /**
     * @param array<string, string>|AbstractStimulusDto $attributes
     */
    public function prepend(array|AbstractStimulusDto $attributes): self
    {
        return $this->mergeAttribute($attributes, true);
    }

    /**
     * @param array<string, string>|AbstractStimulusDto $other
     */
    private function mergeAttribute(array|AbstractStimulusDto $other, bool $prepend = false): self
    {
        $otherAttributes = $other instanceof AbstractStimulusDto ? $other->toArray() : $other;
        $selfAttributes = $this->attributes;

        $attributes = array_merge($selfAttributes, $otherAttributes);
        foreach ($selfAttributes as $name => $attr) {
            $attributes[$name] =
                trim(
                    ($prepend ? $otherAttributes[$name] ?? '' : $attr)
                    .' '.
                    ($prepend ? $attr : $otherAttributes[$name] ?? ''),
                    ' '
                );
        }

        return new self($attributes);
    }
}
