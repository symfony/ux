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
 * Represents a version number, following the SemVer specification.
 *
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class Version implements \Stringable
{
    /**
     * @param non-empty-string
     */
    public function __construct(
        public readonly string $value,
    ) {
    }

    public function isHigherThan(self $version): bool
    {
        return version_compare($this->value, $version->value, '>');
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
