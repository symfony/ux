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
 * Represents a dependency on an Importmap package.
 *
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class ImportmapPackageDependency implements DependencyInterface
{
    /**
     * @param non-empty-string $package
     */
    public function __construct(
        public readonly string $package,
    ) {
    }

    public function isEquivalentTo(DependencyInterface $dependency): bool
    {
        if (!$dependency instanceof self) {
            return false;
        }

        return $this->package === $dependency->package;
    }

    public function toDebug(): string
    {
        return \sprintf('Importmap package "%s"', $this->package);
    }

    public function __toString(): string
    {
        return $this->package;
    }
}
