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
 * Detects directories at the kit root that look like a recipe but have no manifest.json.
 *
 * The directories holding the kit-global files, declared in the kit "copy-files", are ignored.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class MissingRecipeManifestChecker implements KitCheckerInterface
{
    public function check(Kit $kit): iterable
    {
        $entries = scandir($kit->absolutePath);
        if (false === $entries) {
            return;
        }

        // Directories holding the kit-global files (e.g. "assets/" for "assets/fonts/") are not recipes
        $kitFilesDirs = [];
        foreach (array_keys($kit->manifest->copyFiles) as $source) {
            $kitFilesDirs[explode('/', Path::canonicalize($source), 2)[0]] = true;
        }

        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry || isset($kitFilesDirs[$entry])) {
                continue;
            }

            $dir = Path::join($kit->absolutePath, $entry);
            if (!is_dir($dir)) {
                continue;
            }

            if (!file_exists(Path::join($dir, 'manifest.json'))) {
                yield new LintIssue(
                    severity: LintSeverity::Error,
                    category: 'manifest.missing',
                    message: \sprintf('Directory "%s/" looks like a recipe but contains no "manifest.json".', $entry),
                    recipe: $entry,
                );
            }
        }
    }
}
