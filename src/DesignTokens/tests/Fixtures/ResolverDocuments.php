<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Fixtures;

use Symfony\UX\DesignTokens\Resolver\ResolverDocument;
use Symfony\UX\DesignTokens\Resolver\ResolverSource;

final class ResolverDocuments
{
    /**
     * @param array<string, mixed> $inputs
     *
     * @return list<array<array-key, mixed>>
     */
    public static function sources(ResolverDocument $document, array $inputs = []): array
    {
        return array_map(static fn (ResolverSource $source): array => $source->tokens, $document->sourceDescriptors($inputs));
    }

    /**
     * @param array<string, mixed> $inputs
     *
     * @return array<array-key, mixed>
     */
    public static function merged(ResolverDocument $document, array $inputs = []): array
    {
        $merged = [];
        foreach (self::sources($document, $inputs) as $source) {
            $merged = self::merge($merged, $source);
        }

        return $merged;
    }

    /**
     * @param array<array-key, mixed> $base
     * @param array<array-key, mixed> $override
     *
     * @return array<array-key, mixed>
     */
    private static function merge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            $base[$key] = isset($base[$key]) && \is_array($base[$key]) && \is_array($value) && !self::isToken($base[$key]) && !self::isToken($value) && !array_is_list($value)
                ? self::merge($base[$key], $value)
                : $value;
        }

        return $base;
    }

    /** @param array<array-key, mixed> $value */
    private static function isToken(array $value): bool
    {
        return \array_key_exists('$value', $value) || \array_key_exists('$ref', $value);
    }
}
