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
 * One-shot helper to migrate ux.symfony.com's `<kit>/<recipe>.md.twig` docs into a `README.md`: it turns
 * the `toolkit_code_*()` helpers into `::: example` directives and lays the blocks out as the full,
 * self-contained document body (title, description, Demo, Installation, Usage, Examples, API
 * Reference), the same layout the default rendering produces. The output is a starting point meant to
 * be reviewed by hand.
 *
 * @internal
 */
final class MdTwigConverter
{
    public static function convert(string $mdTwig, string $name, string $description): string
    {
        $blocks = [];
        if (preg_match_all('/\{%\s*block\s+(?<name>\w+)\s*%\}(?<content>.*?)\{%\s*endblock\b[^%]*%\}/s', $mdTwig, $matches, \PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $blocks[$match['name']] = self::convertHelpers(trim($match['content']));
            }
        }

        $parts = [
            \sprintf('# %s', $name),
            trim($description),
            $blocks['demo'] ?? '::: example Demo',
            "## Installation\n\n::: installation",
        ];
        if (isset($blocks['usage'])) {
            $parts[] = "## Usage\n\n".$blocks['usage'];
        }
        if (isset($blocks['accessibility'])) {
            $parts[] = "## Accessibility\n\n".$blocks['accessibility'];
        }
        if (isset($blocks['examples'])) {
            $parts[] = "## Examples\n\n".$blocks['examples'];
        }
        $parts[] = "## API Reference\n\n::: api-reference";

        return implode("\n\n", $parts)."\n";
    }

    private static function convertHelpers(string $content): string
    {
        $content = preg_replace_callback(
            '/\{\{\s*toolkit_code_example\([^,]+,\s*[^,]+,\s*([\'"])(?<name>[^\'"]*)\1\s*(?:,\s*(?<options>\{[^}]*\}))?\s*\)\s*\}\}/',
            static fn (array $m): string => '::: example '.$m['name'].self::options($m['options'] ?? ''),
            $content,
        );

        $content = preg_replace_callback(
            '/\{\{\s*toolkit_code_demo\([^)]*?(?:,\s*(?<options>\{[^}]*\}))?\s*\)\s*\}\}/',
            static fn (array $m): string => '::: example Demo'.self::options($m['options'] ?? ''),
            $content,
        );

        return preg_replace('/\{\{\s*toolkit_code_usage\([^)]*\)\s*\}\}/', '::: example Usage', $content);
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
