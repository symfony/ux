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

    /**
     * Built on the first removal by id, then kept up to date.
     *
     * @var array<string, array<int, Element>>|null
     */
    private ?array $elementsById = null;

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
        $this->elements->offsetSet($element);

        if (null !== $this->elementsById && null !== $element->id) {
            $this->elementsById[$element->id][spl_object_id($element)] = $element;
        }

        return $this;
    }

    private function getElementById(string $id): ?Element
    {
        if (null === $this->elementsById) {
            $this->elementsById = [];
            foreach ($this->elements as $element) {
                if (null !== $element->id) {
                    $this->elementsById[$element->id][spl_object_id($element)] = $element;
                }
            }
        }

        if (!isset($this->elementsById[$id])) {
            return null;
        }

        $elements = $this->elementsById[$id];

        return $elements[array_key_first($elements)];
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

            if (null !== $this->elementsById && null !== $element->id) {
                unset($this->elementsById[$element->id][spl_object_id($element)]);
                if ([] === ($this->elementsById[$element->id] ?? null)) {
                    unset($this->elementsById[$element->id]);
                }
            }
        }

        return $this;
    }

    public function removeAll(): static
    {
        $this->elements->removeAll($this->elements);
        $this->elementsById = null;

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
