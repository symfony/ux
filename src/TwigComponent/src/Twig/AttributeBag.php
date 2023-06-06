<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Twig;

/**
 * thanks to @giorgiopogliani!
 * This file is inspired by: https://github.com/giorgiopogliani/twig-components.
 *
 * @author Mathéo Daninos <matheo.daninos@gmail.com>
 */
class AttributeBag implements \ArrayAccess, \IteratorAggregate
{
    protected $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;

        if (\array_key_exists('attributes', $this->attributes) && $this->attributes['attributes'] instanceof ComponentAttributeBag) {
            $parentAttributes = $this->attributes['attributes'];
            unset($this->attributes['attributes']);
            $this->attributes = $this->merge($parentAttributes->getAttributes())->getAttributes();
        }
    }

    public function first($default = null): mixed
    {
        return $this->getIterator()->current() ?? $default;
    }

    public function get($key, $default = ''): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function has($key): bool
    {
        return \array_key_exists($key, $this->attributes);
    }

    public function only($keys): self
    {
        if (null === $keys) {
            $values = $this->attributes;
        } else {
            $keys = \is_array($keys) ? $keys : [$keys];

            $values = array_filter(
                $this->attributes,
                function ($key) use ($keys) {
                    return \in_array($key, $keys);
                },
                \ARRAY_FILTER_USE_KEY
            );
        }

        return new static($values);
    }

    public function except($keys): self
    {
        if (null === $keys) {
            $values = $this->attributes;
        } else {
            $keys = \is_array($keys) ? $keys : [$keys];

            $values = array_filter(
                $this->attributes,
                function ($key) use ($keys) {
                    return !\in_array($key, $keys);
                },
                \ARRAY_FILTER_USE_KEY
            );
        }

        return new static($values);
    }

    public function merge(array $attributeDefaults = []): self
    {
        $attributes = $this->getAttributes();

        foreach ($attributeDefaults as $key => $value) {
            if (!\array_key_exists($key, $attributes)) {
                $attributes[$key] = '';
            }
        }

        foreach ($attributes as $key => $value) {
            $attributes[$key] = trim($value.' '.($attributeDefaults[$key] ?? ''));
        }

        return new static($attributes);
    }

    public function class($defaultClass = ''): self
    {
        return $this->merge(['class' => $defaultClass]);
    }

    public function getAttributes(): mixed
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): void
    {
        if (isset($attributes['attributes']) &&
            $attributes['attributes'] instanceof self) {
            $parentBag = $attributes['attributes'];

            unset($attributes['attributes']);

            $attributes = $parentBag->merge($attributes, $escape = false)->getAttributes();
        }

        $this->attributes = $attributes;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->attributes[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->attributes);
    }

    public function __toString(): string
    {
        $string = '';

        foreach ($this->attributes as $key => $value) {
            if (false === $value || null === $value) {
                continue;
            }

            if (true === $value) {
                $value = $key;
            }

            if (\is_array($value)) {
                $convertedArray = '[';
                foreach ($value as $key => $item) {
                    $convertedArray .= $key.'=>'.$item.',';
                }

                $convertedArray = rtrim($convertedArray, ',');
                $convertedArray .= ']';
                $value = $convertedArray;
            }

            $string .= ' '.$key.'="'.str_replace('"', '\\"', trim($value)).'"';
        }

        return trim($string);
    }
}
