<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Markdown\Extension\Example;

use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Assert;
use Symfony\UX\Toolkit\Recipe\Recipe;

/**
 * Shared handling of a `::: example <Name> {json}` argument, used both when building the AST
 * (HTML rendering) and when flattening examples to fenced code (Markdown rendering).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ExampleResolver
{
    /**
     * Splits the directive argument into the example name (which may contain spaces) and its
     * optional trailing JSON options.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    public static function parse(string $argument): array
    {
        $name = trim($argument);
        $options = [];

        if (preg_match('/^(.*?)\s*(\{.*\})$/s', $name, $matches) && \is_array($decoded = json_decode($matches[2], true))) {
            $name = rtrim($matches[1]);
            $options = $decoded;
        }

        return [$name, $options];
    }

    public static function readCode(Recipe $recipe, string $name): string
    {
        // The name comes from author-controlled Markdown: reject any "../" traversal.
        Assert::pathDoesNotEscapeDirectory($name);

        $file = Path::join($recipe->absolutePath, 'examples', $name.'.html.twig');
        if (!is_file($file)) {
            throw new \InvalidArgumentException(\sprintf('Example "%s" does not exist for recipe "%s": expected a file at "%s".', $name, $recipe->name, $file));
        }

        return trim((string) file_get_contents($file));
    }
}
