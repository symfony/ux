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

use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\UX\Toolkit\File;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\Lint\KitCheckerInterface;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Recipe\RecipeType;

/**
 * Reports the files that a recipe installs over the files of a component recipe.
 *
 * Blocks may share files with each other, as an application installs one of them.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class CopyFilesOverwriteChecker implements KitCheckerInterface
{
    public function check(Kit $kit): iterable
    {
        $components = [];
        foreach ($kit->getRecipes(RecipeType::Component) as $recipe) {
            foreach ($this->getFiles($recipe) as $file) {
                $components[$file->destinationRelativePathName] ??= $recipe->name;
            }
        }

        foreach ($kit->getRecipes() as $recipe) {
            foreach ($this->getFiles($recipe) as $file) {
                $component = $components[$file->destinationRelativePathName] ?? $recipe->name;
                if ($component === $recipe->name) {
                    continue;
                }

                yield new LintIssue(
                    severity: LintSeverity::Error,
                    category: 'copy-files.overwrite',
                    message: \sprintf('"%s" is also installed by the "%s" recipe: installing both overwrites one of them.', $file->destinationRelativePathName, $component),
                    recipe: $recipe->name,
                    file: $file->sourceRelativePathName,
                );
            }
        }
    }

    /**
     * @return iterable<File>
     */
    private function getFiles(Recipe $recipe): iterable
    {
        try {
            return $recipe->getFiles();
        } catch (DirectoryNotFoundException) {
            // reported by CopyFilesExistenceChecker
            return [];
        }
    }
}
