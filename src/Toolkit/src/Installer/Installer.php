<?php

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
use Symfony\UX\Toolkit\File;
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

    public function installRecipe(Kit $kit, Recipe $recipe, string $destinationPath, bool $force, ?ComponentDirectory $componentDirectory = null): InstallationReport
    {
        $pool = $this->poolResolver->resolveForRecipe($kit, $recipe);
        $output = $this->handlePool($pool, $kit, $destinationPath, $force, $componentDirectory ?? new ComponentDirectory());

        return $output;
    }

    /**
     * @param non-empty-string $destinationPath
     */
    private function handlePool(Pool $pool, Kit $kit, string $destinationPath, bool $force, ComponentDirectory $componentDirectory): InstallationReport
    {
        $installedFiles = [];
        $rewriter = $this->createComponentNameRewriter($kit, $componentDirectory);

        foreach ($pool->getFiles() as $recipeAbsolutePath => $files) {
            foreach ($files as $file) {
                $destinationRelativePathName = $componentDirectory->resolveDestination($file->destinationRelativePathName);

                $sourceAbsolutePathName = Path::join($recipeAbsolutePath, $file->sourceRelativePathName);
                $destinationAbsolutePathName = Path::join($destinationPath, $destinationRelativePathName);

                // Last-line defense: even though RecipeManifest and File already reject paths
                // escaping their directory, re-check the fully resolved paths right before the
                // filesystem read/write, so the copy can never land outside its base directory.
                if (!Path::isBasePath($recipeAbsolutePath, $sourceAbsolutePathName)) {
                    throw new \RuntimeException(\sprintf('Refusing to read "%s": source escapes the recipe directory.', $file->sourceRelativePathName));
                }
                if (!Path::isBasePath($destinationPath, $destinationAbsolutePathName)) {
                    throw new \RuntimeException(\sprintf('Refusing to write "%s": destination escapes the target directory.', $destinationRelativePathName));
                }

                if ($this->copyFile($sourceAbsolutePathName, $destinationAbsolutePathName, $force, $rewriter)) {
                    $installedFiles[] = new File($file->sourceRelativePathName, $destinationRelativePathName);
                }
            }
        }

        return new InstallationReport(newFiles: $installedFiles, suggestedPhpPackages: $pool->getPhpPackageDependencies(), suggestedNpmPackages: $pool->getNpmPackageDependencies(), suggestedImportmapPackages: $pool->getImportmapPackageDependencies());
    }

    private function copyFile(string $sourceAbsolutePathName, string $destinationAbsolutePathName, bool $force, ?ComponentNameRewriter $rewriter): bool
    {
        if ($this->filesystem->exists($destinationAbsolutePathName) && !$force) {
            if (!($this->askConfirmation)(\sprintf('File "%s" already exists. Do you want to overwrite it?', $destinationAbsolutePathName))) {
                return false;
            }
        }

        if (null !== $rewriter && str_ends_with($sourceAbsolutePathName, '.html.twig')) {
            $this->filesystem->dumpFile($destinationAbsolutePathName, $rewriter->rewrite(file_get_contents($sourceAbsolutePathName)));

            return true;
        }

        $this->filesystem->copy($sourceAbsolutePathName, $destinationAbsolutePathName, $force);

        return true;
    }

    /**
     * The rewriter is only needed when the destination directory changes the Twig name of the
     * kit components; otherwise files are copied as-is.
     */
    private function createComponentNameRewriter(Kit $kit, ComponentDirectory $componentDirectory): ?ComponentNameRewriter
    {
        if ('' === $prefix = $componentDirectory->getComponentNamePrefix()) {
            return null;
        }

        // Names are collected from the whole kit, not just the recipes being installed, so a
        // reference to a component installed during an earlier run is rewritten too.
        $componentNames = [];
        foreach ($kit->getRecipes() as $recipe) {
            foreach ($recipe->getFiles() as $file) {
                if (null !== $name = ComponentDirectory::componentName($file->destinationRelativePathName)) {
                    $componentNames[$name] = true;
                }
            }
        }

        return new ComponentNameRewriter($componentNames, $prefix);
    }
}
