<?php

namespace App\UIComponent;

use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Zenstruck\Browser\Component;

class UIComponentCollection implements \IteratorAggregate
{
    public function __construct(
        #[TaggedIterator(UIComponentInterface::class)]
        private iterable $components
    )
    {
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator(iterator_to_array($this->components));
    }

    public function get(string $key): ?UIComponentInterface
    {
        foreach ($this->components as $component) {
            if ($component::getKey() === $key) {
                return $component;
            }
        }

        return null;
    }
}
