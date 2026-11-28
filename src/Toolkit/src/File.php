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
use Symfony\Component\Finder\Finder;

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

        Assert::pathDoesNotEscapeDirectory($this->sourceRelativePathName);
        Assert::pathDoesNotEscapeDirectory($this->destinationRelativePathName);
    }

    /**
     * Lists the files to copy for the "copy-files" entries of a kit or recipe manifest.
     *
     * @param non-empty-string                          $absolutePath the directory the sources are relative to
     * @param array<non-empty-string, non-empty-string> $copyFiles    source => destination
     *
     * @return list<self>
     */
    public static function collect(string $absolutePath, array $copyFiles): array
    {
        $files = [];
        foreach ($copyFiles as $source => $destination) {
            $finder = new Finder()->in(Path::join($absolutePath, $source))->sortByName()->files();
            foreach ($finder as $file) {
                $files[] = new self(Path::join($source, $file->getRelativePathname()), Path::join($destination, $file->getRelativePathname()));
            }
        }

        return $files;
    }

    public function __toString(): string
    {
        return $this->sourceRelativePathName;
    }
}
