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
 * Enforces where component attributes are rendered relative to `attributes.defaults()`.
 *
 * `attributes.defaults()` is for values the consumer may override or extend — in practice
 * `data-controller`/`data-action` plus a few overridable HTML defaults (`type`, `alt`). Identity
 * and state attributes must instead be rendered directly as literal attributes, so they are always
 * present and can neither be silently dropped nor overridden:
 *
 *  - `data-slot` is a structural marker (Shadcn) consumers must never override (checked in every kit);
 *  - every other `data-*`/`aria-*` attribute — ARIA, Stimulus value attributes (`data-<controller>-<key>-value`)
 *    and state `data-*` (`data-state`, `data-open`, `data-size`, ...) — must be a literal attribute so it is
 *    always present; only `data-controller`/`data-action` may stay in `defaults()` (checked only in Tailwind
 *    kits, detected by the `tailwind_merge`/`tailwind_classes` idiom).
 *
 * Non-Tailwind kits (Bootstrap, Common) keep their own idiom (`class` merged inside `defaults()`,
 * no `data-slot`) and are therefore only checked against the universal `data-slot` rule.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class AttributesDefaultsChecker implements KitCheckerInterface
{
    private const DEFAULTS_CALL = '.defaults(';

    private const RE_DATA_SLOT_KEY = '/([\'"])data-slot\1\s*:/';

    /**
     * Captures every `data-*`/`aria-*` key of a `defaults()` dict literal; forbidden ones are those
     * not in {@see self::ALLOWED_IN_DEFAULTS}. Keeping this a category match (rather than an
     * enumerated list of state attributes) means new state attributes are caught automatically.
     */
    private const RE_TAILWIND_KEY = '/([\'"])(?<key>(?:data|aria)-[a-z0-9-]+)\1\s*:/';

    /**
     * Keys allowed inside `defaults()` for Tailwind kits. `data-slot` is allowed here because it is
     * already reported by Rule A, so it is not flagged twice.
     *
     * @var list<string>
     */
    private const ALLOWED_IN_DEFAULTS = ['data-controller', 'data-action', 'data-slot'];

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
        $isTailwind = str_contains($contents, 'tailwind_merge') || str_contains($contents, 'tailwind_classes');

        $offset = 0;
        while (false !== $start = strpos($contents, self::DEFAULTS_CALL, $offset)) {
            $offset = $start + \strlen(self::DEFAULTS_CALL);
            $openParen = $start + \strlen(self::DEFAULTS_CALL) - 1;

            $args = $this->extractBalanced($contents, $openParen);
            if (null === $args) {
                break;
            }

            $line = substr_count($contents, "\n", 0, $start) + 1;

            // Rule A (all kits): `data-slot` must never live inside `defaults()`.
            if (preg_match(self::RE_DATA_SLOT_KEY, $args)) {
                yield new LintIssue(
                    severity: LintSeverity::Error,
                    category: 'component.attributes.data-slot-in-defaults',
                    message: \sprintf('`data-slot` must not be defined inside `attributes.defaults()` (line %d): render it directly as a literal attribute (e.g. `data-slot="..."`). It is a structural marker consumers must not override.', $line),
                    recipe: $recipe,
                    file: $file,
                );
            }

            // Rule B (Tailwind kits only): identity/state attributes must be rendered directly.
            if ($isTailwind) {
                foreach ($this->findForbiddenKeys($args) as $key) {
                    yield new LintIssue(
                        severity: LintSeverity::Error,
                        category: 'component.attributes.state-in-defaults',
                        message: \sprintf('`%s` must not be defined inside `attributes.defaults()` (line %d): only `data-controller` and `data-action` may live in `defaults()`. Render ARIA, Stimulus value (`data-*-value`) and state `data-*` attributes directly so they are always present.', $key, $line),
                        recipe: $recipe,
                        file: $file,
                    );
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    private function findForbiddenKeys(string $args): array
    {
        if (!preg_match_all(self::RE_TAILWIND_KEY, $args, $matches)) {
            return [];
        }

        $keys = array_filter($matches['key'], static fn (string $key): bool => !\in_array($key, self::ALLOWED_IN_DEFAULTS, true));

        return array_values(array_unique($keys));
    }

    /**
     * Returns the substring between the parentheses opened at $openParen, honoring quoted strings so
     * parentheses inside string literals do not unbalance the scan. Returns null when unbalanced.
     */
    private function extractBalanced(string $s, int $openParen): ?string
    {
        $len = \strlen($s);
        $depth = 0;
        $inString = false;
        $quote = '';

        for ($i = $openParen; $i < $len; ++$i) {
            $ch = $s[$i];

            if ($inString) {
                if ($ch === $quote && '\\' !== $s[$i - 1]) {
                    $inString = false;
                }
                continue;
            }

            if ('\'' === $ch || '"' === $ch) {
                $inString = true;
                $quote = $ch;
            } elseif ('(' === $ch) {
                ++$depth;
            } elseif (')' === $ch) {
                if (0 === --$depth) {
                    return substr($s, $openParen + 1, $i - $openParen - 1);
                }
            }
        }

        return null;
    }
}
