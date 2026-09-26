<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Resolver;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Exception\LogicException;
use Symfony\UX\DesignTokens\Exception\RuntimeException;
use Symfony\UX\DesignTokens\Resolver\ArrayDocumentLoader;
use Symfony\UX\DesignTokens\Resolver\ConfiguredTokenResolver;
use Symfony\UX\DesignTokens\Resolver\JsonDocumentLoader;
use Symfony\UX\DesignTokens\Tests\Fixtures\TemporaryDirectory;

#[CoversClass(ConfiguredTokenResolver::class)]
final class ConfiguredTokenResolverTest extends TestCase
{
    public function testMergesConfiguredPathsInOrder(): void
    {
        $resolver = new ConfiguredTokenResolver(new ArrayDocumentLoader([
            '/tokens/a.tokens.json' => ['x' => ['$type' => 'number', '$value' => 1], 'a' => ['$type' => 'number', '$value' => 3]],
            '/tokens/b.tokens.json' => ['x' => ['$type' => 'number', '$value' => 2]],
        ]), ['/tokens/a.tokens.json', '/tokens/b.tokens.json']);

        $resolution = $resolver->resolve([]);

        self::assertSame(2, $resolution->getTokens()['x']->getValue());
        self::assertSame(3, $resolution->getTokens()['a']->getValue());
        self::assertSame(['/tokens/a.tokens.json', '/tokens/b.tokens.json'], \array_slice($resolution->getDocuments(), 0, 2));
    }

    public function testSelectsResolverContexts(): void
    {
        $resolver = self::themeResolver();

        self::assertSame(1, $resolver->resolve([])->getTokens()['c']->getValue());
        self::assertSame(2, $resolver->resolve(['scheme' => 'dark'])->getTokens()['c']->getValue());
        self::assertSame([['scheme' => 'light'], ['scheme' => 'dark']], $resolver->getPermutations());
        self::assertContains('/tokens/theme.resolver.json', $resolver->resolve([])->getDocuments());
    }

    public function testConfiguredPathsApplyAfterTheResolverSelection(): void
    {
        $loader = new ArrayDocumentLoader([
            '/tokens/theme.resolver.json' => [
                'version' => '2025.10',
                'sets' => ['base' => ['sources' => [['c' => ['$type' => 'number', '$value' => 1]]]]],
                'resolutionOrder' => [['$ref' => '#/sets/base']],
            ],
            '/tokens/app.tokens.json' => ['c' => ['$type' => 'number', '$value' => 5]],
        ]);

        $resolver = new ConfiguredTokenResolver($loader, ['/tokens/app.tokens.json'], '/tokens/theme.resolver.json');

        self::assertSame(5, $resolver->resolve([])->getTokens()['c']->getValue());
    }

    public function testLoadsSourcesSelectedByAResolverDocumentOnDisk(): void
    {
        $resolver = new ConfiguredTokenResolver(
            new JsonDocumentLoader(),
            resolverPath: \dirname(__DIR__, 2).'/Fixtures/dtcg/resolver-valid.resolver.json',
        );

        $tokens = $resolver->resolve(['theme' => 'dark'])->getTokens();

        self::assertSame('16px', (string) $tokens['space']['$root']);
        self::assertSame('250ms', (string) $tokens['motion']['duration']);
    }

    public function testRecordsDocumentsReachedThroughReferences(): void
    {
        $resolver = new ConfiguredTokenResolver(new ArrayDocumentLoader([
            '/tokens/base.tokens.json' => ['space' => ['$type' => 'number', 'md' => ['$ref' => 'palette.tokens.json#/scale/md']]],
            '/tokens/palette.tokens.json' => ['scale' => ['$type' => 'number', 'md' => ['$value' => 8]]],
        ]), ['/tokens/base.tokens.json']);

        $resolution = $resolver->resolve([]);

        self::assertSame(8, $resolution->getTokens()['space']['md']->getValue());
        self::assertContains('/tokens/palette.tokens.json', $resolution->getDocuments());
    }

    public function testTraceNamesTheWinningAndReplacedSources(): void
    {
        $resolver = new ConfiguredTokenResolver(new ArrayDocumentLoader([
            '/tokens/base.tokens.json' => ['x' => ['$type' => 'number', '$value' => 1]],
            '/tokens/brand.tokens.json' => ['x' => ['$type' => 'number', '$value' => 2]],
        ]), ['/tokens/base.tokens.json', '/tokens/brand.tokens.json']);

        $resolution = $resolver->trace([]);

        self::assertSame(2, $resolution->getTokens()['x']->getValue());
        self::assertSame('/tokens/brand.tokens.json', $resolution->getSource('x')?->uri);
        self::assertSame(['/tokens/base.tokens.json'], array_map(static fn ($source): ?string => $source->uri, $resolution->getOverrides('x')));
        self::assertSame(['/tokens/base.tokens.json', '/tokens/brand.tokens.json'], \array_slice($resolution->getDocuments(), 0, 2));
    }

    public function testRejectsInputsWithoutAResolverDocument(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('no Resolver document is configured');

        new ConfiguredTokenResolver(new ArrayDocumentLoader([]))->resolve(['scheme' => 'dark']);
    }

    public function testWithoutSourcesTheTreeIsEmpty(): void
    {
        $resolver = new ConfiguredTokenResolver(new ArrayDocumentLoader([]));

        self::assertSame([], $resolver->resolve([])->getTokens());
        self::assertSame([], $resolver->getPermutations());
    }

    public function testRejectsAMissingConfiguredFile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Design token file not found');

        new ConfiguredTokenResolver(new JsonDocumentLoader(), ['/path/that/does/not/exist.tokens.json'])->resolve([]);
    }

    public function testRejectsAnUnreadableConfiguredFile(): void
    {
        $directory = new TemporaryDirectory();
        $path = $directory->write('app.tokens.json', '{}');
        new Filesystem()->chmod($path, 0o000);

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Could not read design token file');

            new ConfiguredTokenResolver(new JsonDocumentLoader(), [$path])->resolve([]);
        } finally {
            $directory->remove();
        }
    }

    #[DataProvider('invalidConfiguredFiles')]
    public function testRejectsAConfiguredFileThatIsNotAJsonObject(string $contents, string $message): void
    {
        $directory = new TemporaryDirectory();

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($message);

            new ConfiguredTokenResolver(new JsonDocumentLoader(), [$directory->write('app.tokens.json', $contents)])->resolve([]);
        } finally {
            $directory->remove();
        }
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidConfiguredFiles(): iterable
    {
        yield 'JSON scalar' => ['42', 'must contain a JSON object'];
        yield 'invalid JSON' => ['{broken json}', 'Invalid JSON in design token file'];
    }

    public function testDescribesTheModifiersOfItsResolverDocument(): void
    {
        $resolver = new ConfiguredTokenResolver(new ArrayDocumentLoader(['theme.resolver.json' => [
            'version' => '2025.10',
            'modifiers' => [
                'scheme' => ['contexts' => ['light' => [], 'dark' => []], 'default' => 'light'],
                'brand' => ['contexts' => ['sky' => [], 'ocean' => []]],
            ],
            'resolutionOrder' => [['$ref' => '#/modifiers/scheme'], ['$ref' => '#/modifiers/brand']],
        ]]), resolverPath: 'theme.resolver.json');

        self::assertSame([
            'scheme' => ['contexts' => ['light', 'dark'], 'default' => 'light'],
            'brand' => ['contexts' => ['sky', 'ocean'], 'default' => null],
        ], $resolver->getModifiers());
    }

    public function testHasNoModifiersWithoutAResolverDocument(): void
    {
        self::assertSame([], new ConfiguredTokenResolver(new ArrayDocumentLoader([]))->getModifiers());
    }

    public function testReadsTheResolverDocumentAndItsSourcesThroughTheLoader(): void
    {
        $resolver = new ConfiguredTokenResolver(new ArrayDocumentLoader([
            'design/theme.resolver.json' => ['version' => '2025.10', 'sets' => ['base' => ['sources' => [['$ref' => 'base.tokens.json']]]], 'resolutionOrder' => [['$ref' => '#/sets/base']]],
            'design/base.tokens.json' => ['a' => ['$type' => 'number', '$value' => 1]],
        ]), resolverPath: 'design/theme.resolver.json');

        self::assertSame(1, $resolver->resolve([])->getTokens()['a']->getValue());
    }

    /** @param class-string<\Throwable> $exception */
    #[DataProvider('unusableResolverFiles')]
    public function testRejectsAResolverFileItCannotUse(?string $contents, string $exception, string $message): void
    {
        $directory = new TemporaryDirectory();
        $path = null === $contents ? $directory->path('missing.resolver.json') : $directory->write('theme.resolver.json', $contents);

        try {
            $this->expectException($exception);
            $this->expectExceptionMessage($message);

            new ConfiguredTokenResolver(resolverPath: $path)->getPermutations();
        } finally {
            $directory->remove();
        }
    }

    /** @return iterable<string, array{string|null, class-string<\Throwable>, string}> */
    public static function unusableResolverFiles(): iterable
    {
        yield 'missing' => [null, RuntimeException::class, 'not found'];
        yield 'invalid JSON' => ['{broken json}', RuntimeException::class, 'Invalid JSON in design token file'];
        yield 'JSON scalar' => ['42', RuntimeException::class, 'JSON object'];
        yield 'numeric property names' => ['{"0":"invalid"}', InvalidArgumentException::class, 'must use string property names'];
    }

    private static function themeResolver(): ConfiguredTokenResolver
    {
        return new ConfiguredTokenResolver(new ArrayDocumentLoader([
            '/tokens/theme.resolver.json' => [
                'version' => '2025.10',
                'modifiers' => [
                    'scheme' => [
                        'contexts' => [
                            'light' => [['c' => ['$type' => 'number', '$value' => 1]]],
                            'dark' => [['c' => ['$type' => 'number', '$value' => 2]]],
                        ],
                        'default' => 'light',
                    ],
                ],
                'resolutionOrder' => [['$ref' => '#/modifiers/scheme']],
            ],
        ]), resolverPath: '/tokens/theme.resolver.json');
    }
}
