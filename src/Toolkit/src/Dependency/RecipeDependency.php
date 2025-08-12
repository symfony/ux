<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Dependency;

/**
 * Represents a dependency on a recipe.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
class RecipeDependency implements DependencyInterface
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly string $name,
    ) {
    }

    public function isEquivalentTo(DependencyInterface $dependency): bool
    {
        if (!$dependency instanceof self) {
            return false;
        }

        return $this->name === $dependency->name;
    }

    public function toDebug(): string
    {
        return \sprintf('Recipe "%s"', $this->__toString());
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
