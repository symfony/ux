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

use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\Lint\KitCheckerInterface;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;

/**
 * A recipe's `README.md` is a self-contained document: it must open with its title as the single
 * level-1 heading (`# Title`), then use level-2 headings (`## `) for its sections. A missing leading
 * title, or a second level-1 heading in the body, would render as more than one page title.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class DocHeadingLevelChecker implements KitCheckerInterface
{
    public function check(Kit $kit): iterable
    {
        foreach ($kit->getRecipes() as $recipe) {
            if (null === $recipe->doc) {
                continue;
            }

            $headings = $this->headings($recipe->doc);
            if ([] === $headings) {
                continue;
            }

            if (1 !== $headings[0]['level']) {
                yield new LintIssue(
                    severity: LintSeverity::Error,
                    category: 'doc.heading_level',
                    message: \sprintf('The "README.md" must open with its title as a level-1 heading ("# "), but starts with "%s".', $headings[0]['text']),
                    recipe: $recipe->name,
                );
            }

            foreach (\array_slice($headings, 1) as $heading) {
                if (1 === $heading['level']) {
                    yield new LintIssue(
                        severity: LintSeverity::Error,
                        category: 'doc.heading_level',
                        message: \sprintf('The "README.md" heading "%s" must start at level 2 ("## "); only the title may be a level-1 heading.', $heading['text']),
                        recipe: $recipe->name,
                    );
                }
            }
        }
    }

    /**
     * @return list<array{level: int, text: string}> the headings found outside fenced code blocks, in order
     */
    private function headings(string $doc): array
    {
        $headings = [];
        $insideFence = false;

        foreach (explode("\n", $doc) as $line) {
            if (preg_match('/^\s*```/', $line)) {
                $insideFence = !$insideFence;

                continue;
            }

            if (!$insideFence && preg_match('/^(?<hashes>#{1,6})(?:\s|$)/', $line, $matches)) {
                $headings[] = ['level' => \strlen($matches['hashes']), 'text' => trim($line)];
            }
        }

        return $headings;
    }
}
