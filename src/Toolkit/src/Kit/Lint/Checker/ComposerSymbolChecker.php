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
use Symfony\UX\Toolkit\Dependency\ConstraintVersion;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\Lint\KitCheckerInterface;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;

/**
 * Heuristic, warning-only consistency check between declared Composer dependencies and
 * the Twig symbols they are known to require.
 *
 * The mapping is curated and intentionally incomplete: false positives are reported as
 * warnings, never errors, because maintaining a complete mapping is fragile.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ComposerSymbolChecker implements KitCheckerInterface
{
    /**
     * Twig symbol => [Composer package => minimum version enabling it (null = any version)].
     *
     * All packages listed for a symbol are required together (AND): the symbol is satisfied
     * only when every one is declared at a compatible version. The html_* filters ship in
     * twig/html-extra (which gates their version), but also need twig/extra-bundle to
     * register the extension in a Symfony application.
     *
     * Symbols are literal substrings searched in template contents (no regex).
     *
     * @var array<string, array<string, ?string>>
     */
    private const REQUIRES = [
        'html_cva' => ['twig/extra-bundle' => null, 'twig/html-extra' => '3.12'],
        'html_classes' => ['twig/extra-bundle' => null, 'twig/html-extra' => null],
        'html_attr_type' => ['twig/extra-bundle' => null, 'twig/html-extra' => '3.24'],
        'html_attr_merge' => ['twig/extra-bundle' => null, 'twig/html-extra' => '3.24'],
        'tailwind_merge' => ['tales-from-a-dev/twig-tailwind-extra' => null],
        'tailwind_classes' => ['tales-from-a-dev/twig-tailwind-extra' => '1.3', 'twig/html-extra' => '3.24', 'symfony/ux-twig-component' => '3.5'],
        'ux_icon' => ['symfony/ux-icons' => null],
        '<twig:ux:icon' => ['symfony/ux-icons' => null],
        '<twig:ux:map' => ['symfony/ux-map' => null],
        'provide(' => ['symfony/ux-twig-component' => '3.1'],
        'inject(' => ['symfony/ux-twig-component' => '3.1'],
    ];

    public function check(Kit $kit): iterable
    {
        foreach ($kit->getRecipes() as $recipe) {
            $templatesDir = Path::join($recipe->absolutePath, 'templates');
            $usedSymbols = is_dir($templatesDir) ? $this->extractUsedSymbols($templatesDir) : [];

            /** @var array<string, PhpPackageDependency> $declared */
            $declared = [];
            foreach ($recipe->manifest->dependencies as $dependency) {
                if ($dependency instanceof PhpPackageDependency) {
                    $declared[$dependency->name] = $dependency;
                }
            }

            // Packages required by at least one used symbol.
            $requiredPackages = [];
            foreach (array_keys($usedSymbols) as $symbol) {
                foreach (array_keys(self::REQUIRES[$symbol]) as $package) {
                    $requiredPackages[$package] = true;
                }
            }

            foreach ($declared as $package => $_dep) {
                $symbols = $this->symbolsRequiring($package);
                if ([] === $symbols) {
                    continue;
                }
                if (!isset($requiredPackages[$package])) {
                    yield new LintIssue(
                        severity: LintSeverity::Warning,
                        category: 'composer.declared-but-unused',
                        message: \sprintf(
                            'Composer dependency "%s" is declared but none of its known symbols (%s) appear in templates.',
                            $package,
                            implode(', ', $symbols),
                        ),
                        recipe: $recipe->name,
                    );
                }
            }

            foreach ($usedSymbols as $symbol => $file) {
                $requirements = self::REQUIRES[$symbol];

                $missing = array_values(array_diff(array_keys($requirements), array_keys($declared)));
                if ([] !== $missing) {
                    yield new LintIssue(
                        severity: LintSeverity::Warning,
                        category: 'composer.symbol-undeclared',
                        message: \sprintf(
                            'Template uses Twig symbol "%s" but its required Composer dependencies are not all declared: %s.',
                            $symbol,
                            implode(', ', $missing),
                        ),
                        recipe: $recipe->name,
                        file: $file,
                    );
                    continue;
                }

                $offending = [];
                foreach ($requirements as $package => $since) {
                    if (null === $since) {
                        continue;
                    }
                    $declaredMin = self::constraintMinVersion($declared[$package]->constraintVersion);
                    if (null !== $declaredMin && version_compare($declaredMin, $since, '<')) {
                        $offending[] = \sprintf('"%s:%s" (need >=%s)', $package, (string) $declared[$package]->constraintVersion, $since);
                    }
                }

                if ([] !== $offending) {
                    yield new LintIssue(
                        severity: LintSeverity::Warning,
                        category: 'composer.symbol-version-insufficient',
                        message: \sprintf(
                            'Template uses Twig symbol "%s" but a required Composer dependency is below its minimum version: %s.',
                            $symbol,
                            implode('; ', $offending),
                        ),
                        recipe: $recipe->name,
                        file: $file,
                    );
                }
            }
        }
    }

    /**
     * @return array<string, string> symbol => first file where it was found
     */
    private function extractUsedSymbols(string $templatesDir): array
    {
        $used = [];
        $finder = new Finder()->in($templatesDir)->files()->name('*.html.twig');
        foreach ($finder as $file) {
            $contents = $file->getContents();
            foreach (array_keys(self::REQUIRES) as $symbol) {
                if (isset($used[$symbol])) {
                    continue;
                }
                if (str_contains($contents, $symbol)) {
                    $used[$symbol] = $file->getRelativePathname();
                }
            }
        }

        return $used;
    }

    /**
     * @return list<string> symbols that list the given package among their requirements
     */
    private function symbolsRequiring(string $package): array
    {
        $symbols = [];
        foreach (self::REQUIRES as $symbol => $packages) {
            if (\array_key_exists($package, $packages)) {
                $symbols[] = $symbol;
            }
        }

        return $symbols;
    }

    /**
     * Extract the lowest version a Composer constraint can accept. Best-effort: takes the
     * first version-looking token from the constraint string. Handles "^X.Y", "~X.Y.Z",
     * ">=X.Y", "X.Y.*", "X.Y.Z", "X.Y || X.Z". Returns null when no version is found.
     */
    private static function constraintMinVersion(?ConstraintVersion $constraint): ?string
    {
        if (null === $constraint) {
            return null;
        }
        if (!preg_match('/\d+(?:\.\d+)*/', (string) $constraint, $matches)) {
            return null;
        }

        return $matches[0];
    }
}
