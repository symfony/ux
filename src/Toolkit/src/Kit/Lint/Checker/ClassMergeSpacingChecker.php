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
 * Detects the class-merge idiom `('<base classes> ' ~ attributes.render('class'))|tailwind_merge`
 * where the base classes literal is missing its trailing space.
 *
 * Without that space, the last class of the literal and the first class coming from `attributes`
 * are concatenated into a single, invalid class name (e.g. `...pe-0` + `foo` => `pe-0foo`), so the
 * consumer's first class is silently dropped.
 *
 * Only the `'<literal>' ~ attributes.render(...)` concatenation is checked: the argument form
 * `style.apply({...}, attributes.render('class'))` inserts the separator itself and is left alone.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ClassMergeSpacingChecker implements KitCheckerInterface
{
    /**
     * A single- or double-quoted string literal directly concatenated with `attributes.render(...)`.
     * The captured `content` never contains a quote, matching the existing checker convention.
     */
    private const RE_CONCAT = '/([\'"])(?<content>[^\'"]*)\1\s*~\s*attributes\.render\(/';

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
        if (!preg_match_all(self::RE_CONCAT, $contents, $matches, \PREG_OFFSET_CAPTURE | \PREG_SET_ORDER)) {
            return;
        }

        foreach ($matches as $match) {
            $content = $match['content'][0];

            // An empty literal (`'' ~ attributes.render('class')`) needs no separator.
            if ('' === $content || preg_match('/\s$/', $content)) {
                continue;
            }

            $line = substr_count($contents, "\n", 0, $match[0][1]) + 1;
            $tail = \strlen($content) > 40 ? '…'.substr($content, -40) : $content;

            yield new LintIssue(
                severity: LintSeverity::Error,
                category: 'component.class.missing-space',
                message: \sprintf(
                    'Missing space before `attributes.render(...)` on line %d: the class list literal ending with "%s" must end with a space, otherwise its last class and the first class coming from `attributes` are merged into a single class name. Add a space before the closing quote.',
                    $line,
                    $tail,
                ),
                recipe: $recipe,
                file: $file,
            );
        }
    }
}
