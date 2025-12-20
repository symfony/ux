<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map;

/**
 * Represents a collection of map elements.
 *
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 *
 * @internal
 */
abstract class Elements
{
    private \SplObjectStorage $elements;

    public function __construct(
        array $elements,
    ) {
        $this->elements = new \SplObjectStorage();
        foreach ($elements as $element) {
            $this->add($element);
        }
    }

    public function add(Element $element): static
    {
        $this->elements[$element] = $element->id ?? $this->elements->getHash($element);

        return $this;
    }

    private function getElementById(string $id): ?Element
    {
        foreach ($this->elements as $element) {
            if ($element->id === $id) {
                return $element;
            }
        }

        return null;
    }

    public function remove(Element|string $elementOrId): static
    {
        if (\is_string($elementOrId)) {
            $element = $this->getElementById($elementOrId);
        } else {
            $element = $elementOrId;
        }

        if (null !== $element && $this->elements->offsetExists($element)) {
            unset($this->elements[$element]);
        }

        return $this;
    }

    public function removeAll(): static
    {
        $this->elements->removeAll($this->elements);

        return $this;
    }

    public function toArray(): array
    {
        foreach ($this->elements as $element) {
            $elements[] = $element->toArray();
        }

        return $elements ?? [];
    }

    abstract public static function fromArray(array $elements): self;
}
