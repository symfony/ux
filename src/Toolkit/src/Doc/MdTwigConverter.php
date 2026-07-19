<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Doc;

/**
 * One-shot helper to migrate ux.symfony.com's `<kit>/<recipe>.md.twig` docs into a portable `doc.md`:
 * the Twig scaffolding is dropped (the recipe template regenerates it), `usage`/`examples`/`accessibility`
 * blocks become sections, and `toolkit_code_*()` calls become `::: example` directives. The output is a
 * starting point meant to be reviewed by hand.
 *
 * @internal
 */
final class MdTwigConverter
{
    private const SECTION_TITLES = [
        'usage' => 'Usage',
        'examples' => 'Examples',
        'accessibility' => 'Accessibility',
    ];

    public static function convert(string $mdTwig): string
    {
        $out = [];
        $currentBlock = null;

        foreach (explode("\n", $mdTwig) as $line) {
            if (preg_match('/^\s*\{%\s*extends\b/', $line)) {
                continue;
            }

            if (preg_match('/^\s*\{%\s*block\s+(?<name>\w+)\s*%\}/', $line, $matches)) {
                $currentBlock = $matches['name'];
                if (isset(self::SECTION_TITLES[$currentBlock])) {
                    $out[] = '## '.self::SECTION_TITLES[$currentBlock];
                    $out[] = '';
                }

                continue;
            }

            if (preg_match('/^\s*\{%\s*endblock\b.*%\}/', $line)) {
                $currentBlock = null;

                continue;
            }

            // The recipe template already renders the Demo, so the `demo` block is dropped.
            if ('demo' === $currentBlock) {
                continue;
            }

            $out[] = self::convertHelpers($line);
        }

        return trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $out)))."\n";
    }

    private static function convertHelpers(string $line): string
    {
        $line = preg_replace_callback(
            '/\{\{\s*toolkit_code_example\([^,]+,\s*[^,]+,\s*([\'"])(?<name>[^\'"]*)\1\s*(?:,\s*(?<options>\{[^}]*\}))?\s*\)\s*\}\}/',
            static fn (array $m): string => '::: example '.$m['name'].self::options($m['options'] ?? ''),
            $line,
        );

        $line = preg_replace_callback(
            '/\{\{\s*toolkit_code_demo\([^)]*?(?:,\s*(?<options>\{[^}]*\}))?\s*\)\s*\}\}/',
            static fn (array $m): string => '::: example Demo'.self::options($m['options'] ?? ''),
            $line,
        );

        return preg_replace('/\{\{\s*toolkit_code_usage\([^)]*\)\s*\}\}/', '::: example Usage', $line);
    }

    private static function options(string $twigMap): string
    {
        $inner = trim($twigMap, "{} \t");
        if ('' === $inner) {
            return '';
        }

        $pairs = [];
        foreach (preg_split('/\s*,\s*/', $inner) as $pair) {
            if (!preg_match('/^(?<key>\w+)\s*:\s*(?<value>.+)$/', trim($pair), $matches)) {
                continue;
            }

            $value = trim($matches['value']);
            if (preg_match('/^([\'"])(.*)\1$/s', $value, $stringValue)) {
                $value = json_encode($stringValue[2]);
            }

            $pairs[] = json_encode($matches['key']).': '.$value;
        }

        return [] === $pairs ? '' : ' {'.implode(', ', $pairs).'}';
    }
}
