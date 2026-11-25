<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Kit\Lint\Checker;

use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Installer\InstallationDirectories;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\Lint\KitCheckerInterface;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;

/**
 * Suggests a placeholder for every `copy-files` destination that installs files into a default directory.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class CopyFilesPlaceholderChecker implements KitCheckerInterface
{
    private const DEFAULT_DIRECTORIES = [
        InstallationDirectories::COMPONENTS_DIR_PLACEHOLDER => InstallationDirectories::DEFAULT_COMPONENTS_DIR,
        InstallationDirectories::CONTROLLERS_DIR_PLACEHOLDER => InstallationDirectories::DEFAULT_CONTROLLERS_DIR,
    ];

    public function check(Kit $kit): iterable
    {
        yield from $this->checkCopyFiles($kit->absolutePath, $kit->manifest->copyFiles, null);

        foreach ($kit->getRecipes() as $recipe) {
            yield from $this->checkCopyFiles($recipe->absolutePath, $recipe->manifest->copyFiles, $recipe->name);
        }
    }

    /**
     * @param array<non-empty-string, non-empty-string> $copyFiles
     * @param ?string                                   $recipe    null for the kit-global files
     *
     * @return iterable<LintIssue>
     */
    private function checkCopyFiles(string $absolutePath, array $copyFiles, ?string $recipe): iterable
    {
        foreach ($copyFiles as $source => $destination) {
            foreach (self::DEFAULT_DIRECTORIES as $placeholder => $directory) {
                if (!$this->installsInto($absolutePath, $source, $destination, $directory)) {
                    continue;
                }

                yield new LintIssue(
                    severity: LintSeverity::Warning,
                    category: 'copy-files.placeholder',
                    message: \sprintf('copy-files destination "%s" installs files into the default directory "%s", so it ignores the application configuration: use the "%s" placeholder instead.', $destination, $directory, $placeholder),
                    recipe: $recipe,
                    file: $source,
                );
            }
        }
    }

    private function installsInto(string $basePath, string $source, string $destination, string $directory): bool
    {
        if (Path::isBasePath($directory, $destination)) {
            return true;
        }

        // A parent directory installs into the default one when the source has the matching subdirectory.
        return Path::isBasePath($destination, $directory)
            && file_exists(Path::join($basePath, $source, Path::makeRelative($directory, $destination)));
    }
}
