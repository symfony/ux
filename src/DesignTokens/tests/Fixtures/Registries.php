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

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\UX\DesignTokens\Resolver\ArrayDocumentLoader;
use Symfony\UX\DesignTokens\Resolver\ConfiguredTokenResolver;
use Symfony\UX\DesignTokens\Resolver\JsonDocumentLoader;
use Symfony\UX\DesignTokens\TokenRegistry;

final class Registries
{
    /**
     * @param array<array-key, mixed>         $document
     * @param array<string, string|int|float> $defaultInputs
     */
    public static function fromArray(array $document, array $defaultInputs = []): TokenRegistry
    {
        return new TokenRegistry(new ConfiguredTokenResolver(new ArrayDocumentLoader(['memory.tokens.json' => $document]), ['memory.tokens.json']), $defaultInputs);
    }

    public static function fromJson(string $json): TokenRegistry
    {
        $document = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_array($document));

        return self::fromArray($document);
    }

    /**
     * @param list<string>                    $paths
     * @param array<string, string|int|float> $resolverInputs
     */
    public static function fromFiles(array $paths = [], ?string $resolverPath = null, array $resolverInputs = [], ?CacheInterface $cache = null, bool $debug = false): TokenRegistry
    {
        return new TokenRegistry(new ConfiguredTokenResolver(new JsonDocumentLoader(), $paths, $resolverPath, $cache, $debug), $resolverInputs);
    }

    public static function colorScheme(string $resolver = 'theme.resolver.json'): TokenRegistry
    {
        return self::fromFiles(resolverPath: __DIR__.'/color-scheme/'.$resolver);
    }
}
