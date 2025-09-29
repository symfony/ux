<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Recipe;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\UX\Toolkit\File;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Recipe
{
    /**
     * @param non-empty-string $name
     * @param non-empty-string $absolutePath
     */
    public function __construct(
        public readonly string $name,
        public readonly string $absolutePath,
        public readonly RecipeManifest $manifest,
    ) {
        if (!Path::isAbsolute($this->absolutePath)) {
            throw new \InvalidArgumentException(\sprintf('Kit path "%s" is not absolute.', $this->absolutePath));
        }
    }

    /**
     * @return iterable<File>
     */
    public function getFiles(): iterable
    {
        foreach ($this->manifest->copyFiles as $source => $destination) {
            $finder = (new Finder())->in(Path::join($this->absolutePath, $source))->sortByName()->files();

            foreach ($finder as $file) {
                yield new File(Path::join($source, $file->getRelativePathname()), Path::join($destination, $file->getRelativePathname()));
            }
        }
    }
}
