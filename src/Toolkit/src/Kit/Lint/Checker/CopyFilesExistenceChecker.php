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
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\Lint\KitCheckerInterface;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;

/**
 * Verifies every `copy-files` source path, of the kit and of its recipes, exists on disk and is non-empty.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class CopyFilesExistenceChecker implements KitCheckerInterface
{
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
            $sourcePath = Path::join($absolutePath, $source);

            if (!file_exists($sourcePath)) {
                yield new LintIssue(
                    severity: LintSeverity::Error,
                    category: 'copy-files.missing',
                    message: \sprintf('copy-files source "%s" does not exist.', $source),
                    recipe: $recipe,
                    file: $source,
                );
                continue;
            }

            if (is_dir($sourcePath) && [] === array_diff(scandir($sourcePath) ?: [], ['.', '..'])) {
                yield new LintIssue(
                    severity: LintSeverity::Warning,
                    category: 'copy-files.empty',
                    message: \sprintf('copy-files source directory "%s" is empty.', $source),
                    recipe: $recipe,
                    file: $source,
                );
            }
        }
    }
}
