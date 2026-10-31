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
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\DesignTokens\Exception\ResolverException;
use Symfony\UX\DesignTokens\Exception\UnexpectedValueException;
use Symfony\UX\DesignTokens\Resolver\ArrayDocumentLoader;
use Symfony\UX\DesignTokens\Resolver\ConfiguredTokenResolver;
use Symfony\UX\DesignTokens\Resolver\DocumentLoaderInterface;
use Symfony\UX\DesignTokens\Resolver\JsonDocumentLoader;
use Symfony\UX\DesignTokens\Tests\Fixtures\TemporaryDirectory;

#[CoversClass(ConfiguredTokenResolver::class)]
final class ConfiguredTokenResolverCacheTest extends TestCase
{
    private TemporaryDirectory $directory;
    private ArrayAdapter $cache;

    protected function setUp(): void
    {
        $this->directory = new TemporaryDirectory();
        $this->cache = new ArrayAdapter(deepClone: false);
    }

    protected function tearDown(): void
    {
        $this->directory->remove();
    }

    public function testResolvesEveryTimeWithoutACache(): void
    {
        $loader = self::countingLoader();
        $resolver = new ConfiguredTokenResolver($loader, ['theme.tokens.json']);

        $resolver->resolve([]);
        $resolver->resolve([]);

        self::assertSame(2, $loader->reads('theme.tokens.json'));
    }

    public function testReusesTheCachedEntry(): void
    {
        $loader = self::countingLoader();

        $first = new ConfiguredTokenResolver($loader, ['theme.tokens.json'], cache: $this->cache)->resolve([]);
        $second = new ConfiguredTokenResolver($loader, ['theme.tokens.json'], cache: $this->cache)->resolve([]);

        self::assertSame(1, $loader->reads('theme.tokens.json'));
        self::assertSame(1, $first->getTokens()['theme']->getValue());
        self::assertSame(1, $second->getTokens()['theme']->getValue());
        self::assertSame(['theme.tokens.json'], $second->getDocuments());
    }

    /** @param list<array<string, string>> $inputSets */
    #[DataProvider('inputSets')]
    public function testResolvesOncePerDistinctInputSet(array $inputSets, int $resolutions): void
    {
        $loader = self::countingLoader();
        $resolver = new ConfiguredTokenResolver($loader, resolverPath: 'theme.resolver.json', cache: $this->cache);

        foreach ($inputSets as $inputs) {
            $resolver->resolve($inputs);
        }

        self::assertSame($resolutions, $loader->reads('theme.resolver.json'));
    }

    /** @return iterable<string, array{list<array<string, string>>, int}> */
    public static function inputSets(): iterable
    {
        yield 'each input set apart, whatever the key order' => [[['scheme' => 'dark', 'brand' => 'sky'], ['brand' => 'sky', 'scheme' => 'dark'], ['scheme' => 'light']], 2];
        yield 'inputs spelled with another case once' => [[['scheme' => 'dark'], ['Scheme' => 'dark'], ['scheme' => 'DARK']], 1];
    }

    public function testKeysAnInputThatIsNotUtf8(): void
    {
        $resolver = new ConfiguredTokenResolver(self::countingLoader(), resolverPath: 'theme.resolver.json', cache: $this->cache);

        $this->expectException(ResolverException::class);
        $this->expectExceptionMessage('Invalid context');

        $resolver->resolve(['scheme' => "\xff"]);
    }

    public function testKeepsTwoConfigurationsApart(): void
    {
        $loader = self::countingLoader();

        new ConfiguredTokenResolver($loader, ['theme.tokens.json'], cache: $this->cache)->resolve([]);
        $other = new ConfiguredTokenResolver($loader, ['theme.tokens.json', 'extra.tokens.json'], cache: $this->cache)->resolve([]);

        self::assertSame(2, $loader->reads('theme.tokens.json'));
        self::assertSame(2, $other->getTokens()['extra']->getValue());
    }

    public function testStoresAnExportedTreeWithItsDocuments(): void
    {
        $path = $this->write('theme.tokens.json', 1);

        $this->fileResolver($path, debug: true)->resolve([]);

        $values = $this->cache->getValues();
        $data = reset($values);

        self::assertIsArray($data);
        self::assertSame('number', $data['tokens']['theme']['$type']);
        self::assertContains($path, $data['documents']);
        self::assertNotSame('', $data['signature']);
    }

    public function testResolvesAgainWhenADocumentChangesInDebugMode(): void
    {
        $path = $this->write('theme.tokens.json', 1);

        self::assertSame(1, $this->fileResolver($path, debug: true)->resolve([])->getTokens()['theme']->getValue());

        $this->write('theme.tokens.json', 10);
        new Filesystem()->touch($path, time() + 5);

        self::assertSame(10, $this->fileResolver($path, debug: true)->resolve([])->getTokens()['theme']->getValue());
    }

    public function testResolvesAgainWhenAReferencedDocumentChangesInDebugMode(): void
    {
        $entry = $this->directory->write('base.tokens.json', [
            'space' => ['$type' => 'dimension', 'md' => ['$ref' => 'palette.tokens.json#/scale/md']],
        ]);
        $writePalette = fn (int $value): string => $this->directory->write('palette.tokens.json', [
            'scale' => ['$type' => 'dimension', 'md' => ['$value' => ['value' => $value, 'unit' => 'px']]],
        ]);
        $writePalette(8);

        self::assertSame('8px', (string) $this->fileResolver($entry, debug: true)->resolve([])->getTokens()['space']['md']);

        new Filesystem()->touch($writePalette(16), time() + 5);

        self::assertSame('16px', (string) $this->fileResolver($entry, debug: true)->resolve([])->getTokens()['space']['md']);
    }

    public function testKeepsTheCachedEntryOutsideDebugMode(): void
    {
        $path = $this->write('theme.tokens.json', 1);

        self::assertSame(1, $this->fileResolver($path)->resolve([])->getTokens()['theme']->getValue());

        $this->write('theme.tokens.json', 10);
        new Filesystem()->touch($path, time() + 5);

        self::assertSame(1, $this->fileResolver($path)->resolve([])->getTokens()['theme']->getValue());
    }

    public function testKeepsServingTheEntryOnceItsSourceIsGone(): void
    {
        $path = $this->write('theme.tokens.json', 1);
        $this->fileResolver($path)->resolve([]);

        new Filesystem()->remove($path);

        self::assertSame(1, $this->fileResolver($path)->resolve([])->getTokens()['theme']->getValue());
    }

    public function testRejectsAnEntryItCannotRead(): void
    {
        $resolver = new ConfiguredTokenResolver(self::countingLoader(), ['theme.tokens.json'], cache: $this->cache);
        $resolver->resolve([]);

        foreach (array_keys($this->cache->getValues()) as $key) {
            $item = $this->cache->getItem($key);
            $this->cache->save($item->set(['format' => 1, 'paths' => [], 'signature' => '', 'tokens' => []]));
        }

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Clear the cache to rebuild it');

        $resolver->resolve([]);
    }

    private function write(string $name, int $value): string
    {
        return $this->directory->write($name, ['theme' => ['$type' => 'number', '$value' => $value]]);
    }

    private function fileResolver(string $path, bool $debug = false): ConfiguredTokenResolver
    {
        return new ConfiguredTokenResolver(new JsonDocumentLoader(), [$path], cache: $this->cache, debug: $debug);
    }

    private static function countingLoader(): DocumentLoaderInterface
    {
        $number = static fn (int $value): array => ['$type' => 'number', '$value' => $value];
        $contexts = static fn (string ...$names): array => array_fill_keys($names, [['theme' => $number(1)]]);

        return new class(new ArrayDocumentLoader(['theme.tokens.json' => ['theme' => $number(1)], 'extra.tokens.json' => ['extra' => $number(2)], 'theme.resolver.json' => ['version' => '2025.10', 'modifiers' => ['scheme' => ['contexts' => $contexts('light', 'dark'), 'default' => 'light'], 'brand' => ['contexts' => $contexts('sky', 'ocean'), 'default' => 'sky']], 'resolutionOrder' => [['$ref' => '#/modifiers/scheme'], ['$ref' => '#/modifiers/brand']]]])) implements DocumentLoaderInterface {
            /** @var array<string, int> */
            private array $reads = [];

            public function __construct(private readonly DocumentLoaderInterface $inner)
            {
            }

            public function load(string $uri): array
            {
                $this->reads[$uri] = ($this->reads[$uri] ?? 0) + 1;

                return $this->inner->load($uri);
            }

            public function reads(string $uri): int
            {
                return $this->reads[$uri] ?? 0;
            }
        };
    }
}
