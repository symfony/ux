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
 * One-shot migration: turns a README's `::: example <Name> {opts}` directives into inline ```twig
 * blocks. Runnable examples become ```twig {"preview":true, ...opts}; the `Usage` block stays a plain
 * ```twig (static signature). The example code is read through the given callback.
 *
 * @internal
 */
final class InlineExamplesConverter
{
    /**
     * @param callable(string): string $readExample
     */
    public static function convert(string $readme, callable $readExample): string
    {
        return preg_replace_callback('/^::: example (?<rest>.+)$/m', static function (array $m) use ($readExample): string {
            [$name, $options] = self::parse($m['rest']);
            $code = trim($readExample($name));

            if ('Usage' === $name) {
                return "```twig\n".$code."\n```";
            }

            $info = json_encode(['preview' => true] + $options, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);

            return '```twig '.$info."\n".$code."\n```";
        }, $readme);
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private static function parse(string $argument): array
    {
        $name = trim($argument);
        $options = [];

        if (preg_match('/^(.*?)\s*(\{.*\})$/s', $name, $m) && \is_array($decoded = json_decode($m[2], true))) {
            $name = rtrim($m[1]);
            $options = $decoded;
        }

        return [$name, $options];
    }
}
