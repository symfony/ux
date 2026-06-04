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
 * Twig symbols they are known to provide.
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
     * Composer package => symbol => minimum version that ships the symbol (null = always shipped).
     *
     * Symbols are literal substrings searched in template contents (no regex).
     *
     * @var array<string, array<string, ?string>>
     */
    private const PROVIDES = [
        'twig/extra-bundle' => [
            'html_cva' => '3.12',
            'html_classes' => null,
            'html_attr_type' => '3.24',
            'html_attr_merge' => '3.24',
        ],
        'twig/html-extra' => [
            'html_cva' => '3.12',
            'html_classes' => null,
            'html_attr_type' => '3.24',
            'html_attr_merge' => '3.24',
        ],
        'tales-from-a-dev/twig-tailwind-extra' => [
            'tailwind_merge' => null,
        ],
        'symfony/ux-icons' => [
            'ux_icon' => null,
            '<twig:ux:icon' => null,
        ],
        'symfony/ux-map' => [
            '<twig:ux:map' => null,
        ],
        'symfony/ux-twig-component' => [
            'provide(' => '3.1',
            'inject(' => '3.1',
        ],
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

            foreach ($declared as $package => $_dep) {
                if (!isset(self::PROVIDES[$package])) {
                    continue;
                }
                $knownSymbols = array_keys(self::PROVIDES[$package]);
                if ([] === array_intersect($knownSymbols, array_keys($usedSymbols))) {
                    yield new LintIssue(
                        severity: LintSeverity::Warning,
                        category: 'composer.declared-but-unused',
                        message: \sprintf(
                            'Composer dependency "%s" is declared but none of its known symbols (%s) appear in templates.',
                            $package,
                            implode(', ', $knownSymbols),
                        ),
                        recipe: $recipe->name,
                    );
                }
            }

            foreach ($usedSymbols as $symbol => $file) {
                $providers = $this->providersOf($symbol);
                if ([] === $providers) {
                    continue;
                }

                $declaredProviders = array_values(array_intersect($providers, array_keys($declared)));
                if ([] === $declaredProviders) {
                    yield new LintIssue(
                        severity: LintSeverity::Warning,
                        category: 'composer.symbol-undeclared',
                        message: \sprintf(
                            'Template uses Twig symbol "%s" but none of its known providers (%s) is declared as a Composer dependency.',
                            $symbol,
                            implode(', ', $providers),
                        ),
                        recipe: $recipe->name,
                        file: $file,
                    );
                    continue;
                }

                // A symbol is satisfied if at least one declared provider ships it at a version
                // compatible with the recipe's declared constraint (or has no minimum requirement).
                $offending = [];
                foreach ($declaredProviders as $package) {
                    $since = self::PROVIDES[$package][$symbol];
                    if (null === $since) {
                        $offending = [];
                        break;
                    }
                    $declaredMin = self::constraintMinVersion($declared[$package]->constraintVersion);
                    if (null === $declaredMin || version_compare($declaredMin, $since, '>=')) {
                        $offending = [];
                        break;
                    }
                    $offending[] = \sprintf('"%s:%s" (need >=%s)', $package, (string) $declared[$package]->constraintVersion, $since);
                }

                if ([] !== $offending) {
                    yield new LintIssue(
                        severity: LintSeverity::Warning,
                        category: 'composer.symbol-version-insufficient',
                        message: \sprintf(
                            'Template uses Twig symbol "%s" but no declared provider meets its minimum version: %s.',
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
        $allSymbols = [];
        foreach (self::PROVIDES as $symbols) {
            foreach (array_keys($symbols) as $symbol) {
                $allSymbols[$symbol] = true;
            }
        }

        $used = [];
        $finder = new Finder()->in($templatesDir)->files()->name('*.html.twig');
        foreach ($finder as $file) {
            $contents = $file->getContents();
            foreach ($allSymbols as $symbol => $_) {
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
     * @return list<string>
     */
    private function providersOf(string $symbol): array
    {
        $providers = [];
        foreach (self::PROVIDES as $package => $symbols) {
            if (\array_key_exists($symbol, $symbols)) {
                $providers[] = $package;
            }
        }

        return $providers;
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
