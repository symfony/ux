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

use Symfony\UX\Toolkit\Assert;

/**
 * Represents a dependency on a PHP package.
 *
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class PhpPackageDependency implements Dependency
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly string $name,
        public readonly ?Version $constraintVersion = null,
    ) {
        Assert::phpPackageName($name);
    }

    public function isHigherThan(self $dependency): bool
    {
        if (null === $this->constraintVersion || null === $dependency->constraintVersion) {
            return false;
        }

        return $this->constraintVersion->isHigherThan($dependency->constraintVersion);
    }

    public function __toString(): string
    {
        return $this->name.($this->constraintVersion ? ':^'.$this->constraintVersion : '');
    }
}
