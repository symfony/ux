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
 * Represents a version number, following a simplified version of the SemVer specification.
 *
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class Version implements \Stringable
{
    /**
     * @param int<0, max> $major
     * @param int<0, max> $minor
     * @param int<0, max> $patch
     */
    public function __construct(
        public readonly int $major,
        public readonly int $minor,
        public readonly int $patch,
    ) {
    }

    public function isHigherThan(self $version): bool
    {
        return $this->major > $version->major
            || ($this->major === $version->major && $this->minor > $version->minor)
            || ($this->major === $version->major && $this->minor === $version->minor && $this->patch > $version->patch);
    }

    public function __toString(): string
    {
        return \sprintf('%d.%d.%d', $this->major, $this->minor, $this->patch);
    }
}
