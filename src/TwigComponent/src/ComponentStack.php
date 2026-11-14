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
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @internal
 */
class ComponentStack implements \IteratorAggregate
{
    /**
     * @var MountedComponent[]
     */
    private array $components = [];

    public function push(MountedComponent $components): void
    {
        $this->components[] = $components;
    }

    public function pop(): ?MountedComponent
    {
        if (!$this->components) {
            return null;
        }

        return array_pop($this->components);
    }

    /**
     * The current component being rendered.
     */
    public function getCurrentComponent(): ?MountedComponent
    {
        return end($this->components) ?: null;
    }

    /**
     * The parent of the current component being rendered.
     */
    public function getParentComponent(): ?MountedComponent
    {
        return $this->components[\count($this->components) - 2] ?? null;
    }

    /**
     * The value the closest ancestor of the current component provided for the given key.
     *
     * @internal
     */
    public function getProvidedByAncestor(string $key, mixed $default = null): mixed
    {
        for ($index = \count($this->components) - 2; $index >= 0; --$index) {
            $mounted = $this->components[$index];

            if ($mounted->hasProvided($key)) {
                return $mounted->getProvided($key);
            }
        }

        return $default;
    }

    public function hasParentComponent(): bool
    {
        return (bool) $this->getParentComponent();
    }

    /**
     * @return MountedComponent[]|\ArrayIterator
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator(array_reverse($this->components));
    }

    public function reset(): void
    {
        $this->components = [];
    }
}
