<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Installer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Asset\Component;
use Symfony\UX\Toolkit\File\File;
use Symfony\UX\Toolkit\Kit\Kit;

final class Installer
{
    private PoolResolver $poolResolver;

    /**
     * @param \Closure(string):bool $askConfirmation
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly \Closure $askConfirmation,
    ) {
        $this->poolResolver = new PoolResolver();
    }

    public function installComponent(Kit $kit, Component $component, string $destinationPath, bool $force): InstallationReport
    {
        $pool = $this->poolResolver->resolveForComponent($kit, $component);
        $output = $this->handlePool($pool, $kit, $destinationPath, $force);

        return $output;
    }

    /**
     * @param non-empty-string $destinationPath
     */
    private function handlePool(Pool $pool, Kit $kit, string $destinationPath, bool $force): InstallationReport
    {
        $installedFiles = [];

        foreach ($pool->getFiles() as $file) {
            if ($this->installFile($kit, $file, $destinationPath, $force)) {
                $installedFiles[] = $file;
            }
        }

        return new InstallationReport(newFiles: $installedFiles, suggestedPhpPackages: $pool->getPhpPackageDependencies());
    }

    /**
     * @param non-empty-string $destinationPath
     */
    private function installFile(Kit $kit, File $file, string $destinationPath, bool $force): bool
    {
        $componentPath = Path::join($kit->path, $file->relativePathNameToKit);
        $componentDestinationPath = Path::join($destinationPath, $file->relativePathName);

        if ($this->filesystem->exists($componentDestinationPath) && !$force) {
            if (!($this->askConfirmation)(\sprintf('File "%s" already exists. Do you want to overwrite it?', $componentDestinationPath))) {
                return false;
            }
        }

        $this->filesystem->copy($componentPath, $componentDestinationPath, $force);

        return true;
    }
}
