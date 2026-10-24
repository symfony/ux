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

/**
 * @internal
 */
final class Installer
{
    private PoolResolver $poolResolver;

    /**
     * @param \Closure(string $question, string $existingFile, string $newContents):bool $askConfirmation
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly \Closure $askConfirmation,
        private readonly InstallationDirectories $directories = new InstallationDirectories(),
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
        $componentNameRewriter = ComponentNameRewriter::forKit($kit, $this->directories->getComponentNamePrefix());
        $stimulusIdentifierRewriter = StimulusIdentifierRewriter::forKit($kit, $this->directories->getStimulusIdentifierPrefix());

        foreach ($pool->getFiles() as $recipeAbsolutePath => $files) {
            foreach ($files as $file) {
                $installedFile = new File($file->sourceRelativePathName, $this->directories->resolve($file->destinationRelativePathName));
                $sourceAbsolutePathName = Path::join($recipeAbsolutePath, $installedFile->sourceRelativePathName);
                $destinationAbsolutePathName = Path::join($destinationPath, $installedFile->destinationRelativePathName);

                // Last-line defense: even though RecipeManifest and File already reject paths
                // escaping their directory, re-check the fully resolved paths right before the
                // filesystem read/write, so the copy can never land outside its base directory.
                if (!Path::isBasePath($recipeAbsolutePath, $sourceAbsolutePathName)) {
                    throw new \RuntimeException(\sprintf('Refusing to read "%s": source escapes the recipe directory.', $installedFile->sourceRelativePathName));
                }
                if (!Path::isBasePath($destinationPath, $destinationAbsolutePathName)) {
                    throw new \RuntimeException(\sprintf('Refusing to write "%s": destination escapes the target directory.', $installedFile->destinationRelativePathName));
                }

                $contents = $this->filesystem->readFile($sourceAbsolutePathName);
                if (str_ends_with($sourceAbsolutePathName, '.twig')) {
                    $contents = $stimulusIdentifierRewriter->rewrite($componentNameRewriter->rewrite($contents));
                } elseif (str_ends_with($sourceAbsolutePathName, '.js')) {
                    $contents = $stimulusIdentifierRewriter->rewrite($contents);
                }

                if ($this->writeFile($contents, $destinationAbsolutePathName, $force)) {
                    $installedFiles[] = $installedFile;
                }
            }
        }

        return new InstallationReport(newFiles: $installedFiles, suggestedPhpPackages: $pool->getPhpPackageDependencies(), suggestedNpmPackages: $pool->getNpmPackageDependencies(), suggestedImportmapPackages: $pool->getImportmapPackageDependencies());
    }

    private function writeFile(string $contents, string $destinationAbsolutePathName, bool $force): bool
    {
        if ($this->filesystem->exists($destinationAbsolutePathName) && !$force) {
            if ($this->filesystem->readFile($destinationAbsolutePathName) === $contents) {
                return true;
            }

            $question = \sprintf('File "%s" already exists. Do you want to overwrite it?', $destinationAbsolutePathName);
            if (!($this->askConfirmation)($question, $destinationAbsolutePathName, $contents)) {
                return false;
            }
        }

        $this->filesystem->dumpFile($destinationAbsolutePathName, $contents);

        return true;
    }
}
