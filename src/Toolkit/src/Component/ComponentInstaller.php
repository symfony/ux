<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Component;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Exception\ComponentAlreadyExistsException;
use Symfony\UX\Toolkit\Kit\Kit;

/**
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final readonly class ComponentInstaller
{
    public function __construct(
        private Filesystem $filesystem,
    ) {
    }

    /**
     * @param non-empty-string $destination
     */
    public function install(Kit $kit, Component $component, string $destination, bool $force = false): void
    {
        foreach ($component->files as $file) {
            $componentPath = Path::join($kit->path, $file->relativePathNameToKit);
            $componentDestinationPath = Path::join($destination, $file->relativePathName);

            if ($this->filesystem->exists($componentDestinationPath) && !$force) {
                throw new ComponentAlreadyExistsException($component->name);
            }

            $this->filesystem->copy($componentPath, $componentDestinationPath, $force);
        }
    }
}
