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
                $value = $this->attributes[$key];

                if (null === $value) {
                    trigger_deprecation('symfony/ux-twig-component', '2.8.0', 'Passing "null" as an attribute value is deprecated and will throw an exception in 3.0.');
                    $value = true;
                }

                return match ($value) {
                    true => "{$carry} {$key}",
                    false => $carry,
                    default => sprintf('%s %s="%s"', $carry, $key, $value),
                };
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

    public function add(AbstractStimulusDto $stimulusDto): self
    {
        $controllersAttributes = $stimulusDto->toArray();
        $attributes = $this->attributes;

        $attributes['data-controller'] = trim(implode(' ', array_merge(
            explode(' ', $attributes['data-controller'] ?? ''),
            explode(' ', $controllersAttributes['data-controller'] ?? [])
        )));
        unset($controllersAttributes['data-controller']);

        $clone = new self($attributes);

        // add the remaining attributes for values/classes
        return $clone->defaults($controllersAttributes);
    }
}
