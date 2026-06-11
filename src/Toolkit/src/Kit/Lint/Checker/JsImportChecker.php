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
use Symfony\Component\Finder\Finder;
use Symfony\UX\Toolkit\Dependency\DependencyInterface;
use Symfony\UX\Toolkit\Dependency\ImportmapPackageDependency;
use Symfony\UX\Toolkit\Dependency\NpmPackageDependency;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\Lint\KitCheckerInterface;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;

/**
 * Scans recipe JS files for ES `import` statements and verifies each non-relative package
 * is declared as `npm` or `importmap` dependency.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class JsImportChecker implements KitCheckerInterface
{
    /**
     * Implicit packages always available in a Symfony UX app — never warn for these.
     */
    private const IMPLICIT_PACKAGES = [
        '@hotwired/stimulus',
        '@hotwired/turbo',
        '@symfony/stimulus-bundle',
    ];

    public function check(Kit $kit): iterable
    {
        // Kit-global dependencies are available to every recipe (e.g. a library installed once during the kit setup).
        $kitDeclared = $this->collectDeclaredNames($kit->manifest->dependencies);

        foreach ($kit->getRecipes() as $recipe) {
            $assetsDir = Path::join($recipe->absolutePath, 'assets');
            if (!is_dir($assetsDir)) {
                continue;
            }

            $declared = [...$kitDeclared, ...$this->collectDeclaredNames($recipe->manifest->dependencies)];

            $finder = new Finder()->in($assetsDir)->files()->name(['*.js', '*.ts', '*.mjs']);
            foreach ($finder as $file) {
                $contents = $file->getContents();
                foreach ($this->extractImports($contents) as $packageName) {
                    if (\in_array($packageName, self::IMPLICIT_PACKAGES, true)) {
                        continue;
                    }
                    if ($this->isDeclared($packageName, $declared)) {
                        continue;
                    }

                    yield new LintIssue(
                        severity: LintSeverity::Warning,
                        category: 'js.import.undeclared',
                        message: \sprintf('JS import "%s" is not declared in dependencies.npm or dependencies.importmap.', $packageName),
                        recipe: $recipe->name,
                        file: 'assets/'.$file->getRelativePathname(),
                    );
                }
            }
        }
    }

    /**
     * @param iterable<DependencyInterface> $dependencies
     *
     * @return list<string>
     */
    private function collectDeclaredNames(iterable $dependencies): array
    {
        $declared = [];
        foreach ($dependencies as $dependency) {
            if ($dependency instanceof NpmPackageDependency) {
                $declared[] = $dependency->name;
            } elseif ($dependency instanceof ImportmapPackageDependency) {
                $declared[] = $dependency->package;
            }
        }

        return $declared;
    }

    /**
     * Inspired by Symfony AssetMapper's JavaScriptImportPathCompiler::IMPORT_PATTERN: the first
     * two alternation arms swallow line comments and string literals so the import arms cannot
     * match inside them. Captures the raw spec; relative/absolute filtering happens in PHP.
     */
    private const IMPORT_PATTERN = '#
        ^\h*//.*$
    |
        \'(?:[^\'\\\\\n]|\\\\.)*+\' | "(?:[^"\\\\\n]|\\\\.)*+"
    |
        (?:
            import\s*
                (?:
                    (?:\*\s*as\s+\w+|[\w\s{},*]+)\s*from\s*
                )?
        |
            export\s*
                (?:
                    \*(?:\s*as\s+\w+)?
                |
                    \{[^}]*+\}
                )
                \s*from\s*
        |
            \bimport\(
        |
            \brequire\(
        )
        \s*[\'"`]([^\'"`\n]+)[\'"`]
    #mxu';

    /**
     * @return iterable<string>
     */
    private function extractImports(string $contents): iterable
    {
        if (!preg_match_all(self::IMPORT_PATTERN, $contents, $matches)) {
            return;
        }

        $seen = [];
        foreach ($matches[1] as $spec) {
            if ('' === $spec || '.' === $spec[0] || '/' === $spec[0]) {
                continue;
            }
            $package = $this->toPackageName($spec);
            if (isset($seen[$package])) {
                continue;
            }
            $seen[$package] = true;
            yield $package;
        }
    }

    private function toPackageName(string $spec): string
    {
        if ('@' === $spec[0]) {
            $parts = explode('/', $spec, 3);

            return implode('/', \array_slice($parts, 0, 2));
        }

        return explode('/', $spec, 2)[0];
    }

    /**
     * @param list<string> $declared
     */
    private function isDeclared(string $packageName, array $declared): bool
    {
        foreach ($declared as $entry) {
            if ($entry === $packageName) {
                return true;
            }
            if (str_starts_with($entry, $packageName.'/')) {
                return true;
            }
        }

        return false;
    }
}
