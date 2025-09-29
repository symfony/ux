<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit;

use Symfony\Component\Filesystem\Path;

/**
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class File implements \Stringable
{
    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(
        public readonly string $sourceRelativePathName,
        public readonly string $destinationRelativePathName,
    ) {
        if (!Path::isRelative($this->sourceRelativePathName)) {
            throw new \InvalidArgumentException(\sprintf('The source path "%s" must be relative.', $this->sourceRelativePathName));
        }

        if (!Path::isRelative($this->destinationRelativePathName)) {
            throw new \InvalidArgumentException(\sprintf('The destination path "%s" must be relative.', $this->destinationRelativePathName));
        }
    }

    public function __toString(): string
    {
        return $this->sourceRelativePathName;
    }
}
