<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb;

/**
 * Parses the placeholders of a label, with the syntax of a #[Route] path.
 *
 * - `{slug}` reads the controller argument `$slug`, or else the route parameter `slug`;
 * - `{name:product}` reads the property `name` of the controller argument `$product`;
 * - `{title:product.name}` reads the property path `name` of `$product`, the variable only names the placeholder.
 *
 * @phpstan-type Placeholder array{placeholder: string, variable: string, argument: ?string, path: ?string}
 *
 * @internal
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
final class LabelPattern
{
    private const string REGEX = '/\{([\w\x80-\xFF]++)(?::([\w\x80-\xFF]++)((?:\.[\w\x80-\xFF]++)*+))?\}/';

    /**
     * @return list<Placeholder>
     */
    public static function parse(string $label): array
    {
        if (!str_contains($label, '{') || !preg_match_all(self::REGEX, $label, $matches, \PREG_SET_ORDER | \PREG_UNMATCHED_AS_NULL)) {
            return [];
        }

        return array_map(
            static fn (array $match): array => [
                'placeholder' => $match[0],
                'variable' => $match[1],
                'argument' => $match[2] ?? null,
                'path' => null !== $match[3] && '' !== $match[3] ? substr($match[3], 1) : null,
            ],
            $matches,
        );
    }

    /**
     * The names of the controller arguments the placeholders read.
     *
     * @return list<string>
     */
    public static function arguments(string $label): array
    {
        return array_values(array_unique(array_map(
            static fn (array $placeholder): string => $placeholder['argument'] ?? $placeholder['variable'],
            self::parse($label),
        )));
    }
}
