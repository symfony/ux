<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens;

use Symfony\UX\DesignTokens\Exception\UnexpectedValueException;
use Symfony\UX\DesignTokens\Token\TokenFactory;
use Symfony\UX\DesignTokens\Token\TokenInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class TokenTree
{
    /**
     * @param array<array-key, mixed> $tokens
     *
     * @return array<string, TokenInterface>
     */
    public static function flatten(array $tokens): array
    {
        $flat = [];
        self::append($flat, $tokens, '');

        return $flat;
    }

    /**
     * @param array<array-key, mixed> $tokens
     *
     * @return array<array-key, mixed>
     */
    public static function export(array $tokens): array
    {
        $data = [];
        foreach ($tokens as $name => $value) {
            if (self::isGroupMetadata((string) $name)) {
                $data[$name] = $value;

                continue;
            }
            if ($value instanceof TokenInterface) {
                $node = ['$type' => $value->getType(), '$value' => $value->getValue()];
                if (null !== $value->getDescription()) {
                    $node['$description'] = $value->getDescription();
                }
                if ([] !== $value->getExtensions()) {
                    $node['$extensions'] = $value->getExtensions();
                }
                if ($value->isDeprecated()) {
                    $node['$deprecated'] = $value->getDeprecationMessage() ?? true;
                }
                $data[$name] = $node;
            } elseif (\is_array($value)) {
                $data[$name] = self::export($value);
            }
        }

        return $data;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    public static function hydrate(array $data): array
    {
        $tokens = [];
        foreach ($data as $name => $node) {
            if (self::isGroupMetadata((string) $name)) {
                $tokens[$name] = $node;

                continue;
            }
            if (!\is_array($node)) {
                continue;
            }
            if (!\array_key_exists('$value', $node)) {
                $tokens[$name] = self::hydrate($node);

                continue;
            }

            // Cache data is checked, not asserted: assertions are off in production.
            $type = $node['$type'] ?? null;
            if (!\is_string($type)) {
                throw new UnexpectedValueException(\sprintf('Exported design token "%s" must carry a string $type.', $name));
            }
            $description = $node['$description'] ?? null;
            if (null !== $description && !\is_string($description)) {
                throw new UnexpectedValueException(\sprintf('Exported design token "%s" must carry a string $description.', $name));
            }
            $extensions = $node['$extensions'] ?? [];
            if (!\is_array($extensions)) {
                throw new UnexpectedValueException(\sprintf('Exported design token "%s" must carry an object of $extensions.', $name));
            }
            $deprecated = $node['$deprecated'] ?? null;
            if (null !== $deprecated && !\is_bool($deprecated) && !\is_string($deprecated)) {
                throw new UnexpectedValueException(\sprintf('Exported design token "%s" must carry a boolean or string $deprecated.', $name));
            }

            $tokens[$name] = TokenFactory::create($type, $node['$value'], $description, $extensions, $deprecated);
        }

        return $tokens;
    }

    private static function isGroupMetadata(string $name): bool
    {
        return '$description' === $name || '$extensions' === $name;
    }

    /**
     * @param array<string, TokenInterface> $flat
     * @param array<array-key, mixed>       $tokens
     */
    private static function append(array &$flat, array $tokens, string $prefix): void
    {
        foreach ($tokens as $name => $value) {
            if (self::isGroupMetadata((string) $name)) {
                continue;
            }
            $path = '' === $prefix ? (string) $name : $prefix.'.'.$name;

            if ($value instanceof TokenInterface) {
                $flat[$path] = $value;
            } elseif (\is_array($value)) {
                self::append($flat, $value, $path);
            }
        }
    }
}
