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
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\Lint\KitCheckerInterface;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;

/**
 * Enforces that every component's `{%- props ... -%}` declaration stays on a single line.
 *
 * The per-prop documentation lives in the `{# @prop ... #}` docblock above; the `props` tag itself
 * is only the variable/default declaration and reads best as one line, keeping every kit consistent
 * (Shadcn, Flowbite and Common already do this). A declaration that spills over several lines is
 * flagged so it can be collapsed onto one line.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class PropsSingleLineChecker implements KitCheckerInterface
{
    /**
     * Matches a `props` tag — with or without whitespace-trim markers — capturing everything up to
     * its closing `%}`/`-%}`. The `s` flag lets the body span multiple lines so we can detect one.
     */
    private const RE_PROPS = '/\{%-?\s*props\b(?<body>.*?)-?%\}/s';

    public function check(Kit $kit): iterable
    {
        foreach ($kit->getRecipes() as $recipe) {
            $templatesDir = Path::join($recipe->absolutePath, 'templates');
            if (!is_dir($templatesDir)) {
                continue;
            }

            $finder = new Finder()->in($templatesDir)->files()->name('*.html.twig')->sortByName();
            foreach ($finder as $file) {
                yield from $this->checkFile($recipe->name, $file->getRelativePathname(), $file->getContents());
            }
        }
    }

    /**
     * @return iterable<LintIssue>
     */
    private function checkFile(string $recipe, string $file, string $contents): iterable
    {
        if (!preg_match_all(self::RE_PROPS, $contents, $matches, \PREG_OFFSET_CAPTURE | \PREG_SET_ORDER)) {
            return;
        }

        foreach ($matches as $match) {
            if (!str_contains($match[0][0], "\n")) {
                continue;
            }

            $line = substr_count($contents, "\n", 0, $match[0][1]) + 1;

            yield new LintIssue(
                severity: LintSeverity::Error,
                category: 'component.props.multiline',
                message: \sprintf(
                    'The `props` declaration starting on line %d spans multiple lines: collapse it onto a single line (`{%%- props ... -%%}`). Per-prop documentation belongs in the `{# @prop ... #}` docblock above, not in the `props` tag.',
                    $line,
                ),
                recipe: $recipe,
                file: $file,
            );
        }
    }
}
