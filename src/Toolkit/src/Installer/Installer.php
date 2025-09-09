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
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Recipe\Recipe;

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

    public function installRecipe(Kit $kit, Recipe $recipe, string $destinationPath, bool $force): InstallationReport
    {
        $pool = $this->poolResolver->resolveForRecipe($kit, $recipe);
        $output = $this->handlePool($pool, $kit, $destinationPath, $force);

        return $output;
    }

    /**
     * @param non-empty-string $destinationPath
     */
    private function handlePool(Pool $pool, Kit $kit, string $destinationPath, bool $force): InstallationReport
    {
        $installedFiles = [];

        foreach ($pool->getFiles() as $recipeAbsolutePath => $files) {
            foreach ($files as $file) {
                $sourceAbsolutePathName = Path::join($recipeAbsolutePath, $file->sourceRelativePathName);
                $destinationAbsolutePathName = Path::join($destinationPath, $file->destinationRelativePathName);

                if ($this->copyFile($kit, $sourceAbsolutePathName, $destinationAbsolutePathName, $force)) {
                    $installedFiles[] = $file;
                }
            }
        }

        return new InstallationReport(newFiles: $installedFiles, suggestedPhpPackages: $pool->getPhpPackageDependencies(), suggestedNpmPackages: $pool->getNpmPackageDependencies(), suggestedImportmapPackages: $pool->getImportmapPackageDependencies());
    }

    private function copyFile(Kit $kit, string $sourceAbsolutePathName, string $destinationAbsolutePathName, bool $force): bool
    {
        if ($this->filesystem->exists($destinationAbsolutePathName) && !$force) {
            if (!($this->askConfirmation)(\sprintf('File "%s" already exists. Do you want to overwrite it?', $destinationAbsolutePathName))) {
                return false;
            }
        }

        $this->filesystem->copy($sourceAbsolutePathName, $destinationAbsolutePathName, $force);

        return true;
    }
}
