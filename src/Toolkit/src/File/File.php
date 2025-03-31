<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\File;

use Symfony\Component\Filesystem\Path;

/**
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final readonly class File
{
    /**
     * @param non-empty-string $relativePathNameToKit relative path from the kit root directory, example "templates/components/Table/Body.html.twig"
     * @param non-empty-string $relativePathName      relative path name, without any prefix, example "Table/Body.html.twig"
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        public FileType $type,
        public string $relativePathNameToKit,
        public string $relativePathName,
    ) {
        if (!Path::isRelative($relativePathNameToKit)) {
            throw new \InvalidArgumentException(\sprintf('The path to the kit "%s" must be relative.', $relativePathNameToKit));
        }

        if (!Path::isRelative($relativePathName)) {
            throw new \InvalidArgumentException(\sprintf('The path name "%s" must be relative.', $relativePathName));
        }

        if (!str_ends_with($relativePathNameToKit, $relativePathName)) {
            throw new \InvalidArgumentException(\sprintf('The relative path name "%s" must be a subpath of the relative path to the kit "%s".', $relativePathName, $relativePathNameToKit));
        }
    }

    public function __toString(): string
    {
        return \sprintf('%s (%s)', $this->relativePathNameToKit, $this->type->getLabel());
    }
}
