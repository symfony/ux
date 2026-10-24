<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Exception\ResolverException;
use Symfony\UX\DesignTokens\Exception\TokenNotFoundException;
use Symfony\UX\DesignTokens\Resolver\ArrayDocumentLoader;
use Symfony\UX\DesignTokens\Resolver\ConfiguredTokenResolver;
use Symfony\UX\DesignTokens\Resolver\TokenResolution;
use Symfony\UX\DesignTokens\Resolver\TokenResolverInterface;
use Symfony\UX\DesignTokens\Tests\Fixtures\Registries;
use Symfony\UX\DesignTokens\Tests\Fixtures\TokenValues;
use Symfony\UX\DesignTokens\Token\BorderToken;
use Symfony\UX\DesignTokens\Token\ColorToken;
use Symfony\UX\DesignTokens\Token\DimensionToken;
use Symfony\UX\DesignTokens\Token\NumberToken;
use Symfony\UX\DesignTokens\Token\TypographyToken;
use Symfony\UX\DesignTokens\TokenRegistry;

#[CoversClass(TokenRegistry::class)]
final class TokenRegistryTest extends TestCase
{
    public function testGetReturnsTypedTokens(): void
    {
        $registry = Registries::fromArray([
            'color' => ['brand' => ['$type' => 'color', '$value' => TokenValues::color()]],
            'dimension' => ['$type' => 'dimension', '$value' => TokenValues::dimension(16)],
            'typography' => ['$type' => 'typography', '$value' => TokenValues::typography()],
            'border' => ['$type' => 'border', '$value' => TokenValues::border()],
        ]);

        self::assertInstanceOf(ColorToken::class, $registry->get('color.brand'));
        self::assertInstanceOf(DimensionToken::class, $registry->get('dimension'));
        self::assertInstanceOf(TypographyToken::class, $registry->get('typography'));
        self::assertInstanceOf(BorderToken::class, $registry->get('border'));
    }

    public function testGetThrowsForMissingPaths(): void
    {
        $registry = self::brandRegistry();

        try {
            $registry->get('does.not.exist');
            self::fail('Missing paths should be rejected.');
        } catch (TokenNotFoundException $exception) {
            self::assertSame('does.not.exist', $exception->getPath());
            self::assertStringContainsString('not found', $exception->getMessage());
        }
    }

    public function testGetThrowsForGroups(): void
    {
        $registry = self::brandRegistry();

        $this->expectException(TokenNotFoundException::class);
        $this->expectExceptionMessageMatches('/group/');

        $registry->get('color');
    }

    public function testFindAndHasProvideNonThrowingLookup(): void
    {
        $registry = self::brandRegistry();

        self::assertSame($registry->get('color.brand'), $registry->find('color.brand'));
        self::assertTrue($registry->has('color.brand'));
        self::assertNull($registry->find('color.missing'));
        self::assertFalse($registry->has('color.missing'));
        self::assertNull($registry->find('color'));
        self::assertFalse($registry->has('color'));
    }

    public function testAllReturnsTheNestedTree(): void
    {
        self::assertSame([], new TokenRegistry(new ConfiguredTokenResolver(new ArrayDocumentLoader([])))->all());

        $all = self::brandRegistry()->all();

        self::assertInstanceOf(ColorToken::class, $all['color']['brand']);
    }

    public function testFlattensDefaultAndContextualSelections(): void
    {
        $registry = self::themeRegistry();

        self::assertSame(['theme'], array_keys($registry->flatten()));
        self::assertSame('1', (string) $registry->flatten()['theme']);
        self::assertSame('2', (string) $registry->flatten(['scheme' => 'dark'])['theme']);
        self::assertSame($registry->flatten(), $registry->flatten([]));
    }

    public function testDefaultInputsSelectTheDefaultContext(): void
    {
        $registry = self::themeRegistry(['scheme' => 'dark']);

        self::assertSame('2', (string) $registry->get('theme'));
        self::assertSame($registry->get('theme'), $registry->all(['scheme' => 'dark'])['theme']);
    }

    public function testResolvesAnotherInputWithoutChangingTheDefaultSelection(): void
    {
        $registry = self::themeRegistry(['scheme' => 'light']);

        self::assertSame('1', (string) $registry->get('theme'));
        self::assertSame('2', (string) $registry->all(['scheme' => 'dark'])['theme']);
        self::assertSame('2', (string) $registry->get('theme', ['scheme' => 'dark']));
        self::assertSame('1', (string) $registry->get('theme'));
        self::assertSame($registry->get('theme'), $registry->get('theme', []));
    }

    /** @param list<array<string, string>> $calls */
    #[DataProvider('sharedResolutionProvider')]
    public function testResolvesEachSelectionOnce(array $calls): void
    {
        $resolver = self::spyResolver();
        $registry = new TokenRegistry($resolver, ['scheme' => 'light']);

        foreach ($calls as $inputs) {
            $registry->all($inputs);
        }

        self::assertSame([['scheme' => 'dark']], $resolver->calls);
    }

    /** @return iterable<string, array{list<array<string, string>>}> */
    public static function sharedResolutionProvider(): iterable
    {
        yield 'the same inputs twice' => [[['scheme' => 'dark'], ['scheme' => 'dark']]];
        yield 'inputs spelled with another case' => [[['scheme' => 'dark'], ['Scheme' => 'dark'], ['scheme' => 'DARK']]];
    }

    public function testResolutionsAreKeptUntilReset(): void
    {
        $registry = self::themeRegistry();

        $first = $registry->all(['scheme' => 'dark']);
        $second = $registry->all(['scheme' => 'dark']);

        self::assertSame($first['theme'], $second['theme']);

        $registry->reset();
        $third = $registry->all(['scheme' => 'dark']);

        self::assertNotSame($first['theme'], $third['theme']);
        self::assertEquals($first, $third);
    }

    public function testPermutationsComeFromTheResolver(): void
    {
        self::assertSame([['scheme' => 'light'], ['scheme' => 'dark']], self::themeRegistry()->getPermutations());
    }

    public function testModifiersComeFromTheResolver(): void
    {
        self::assertSame(['scheme' => ['contexts' => ['light', 'dark'], 'default' => 'light']], self::themeRegistry()->getModifiers());
    }

    public function testRejectsAnInputGivenTwiceWithDifferentCasing(): void
    {
        $registry = new TokenRegistry(self::spyResolver());

        $this->expectException(ResolverException::class);
        $this->expectExceptionMessage('Modifier input "scheme" is provided more than once with different casing.');

        $registry->all(['Scheme' => 'dark', 'scheme' => 'light']);
    }

    public function testAnInputThatIsNotUtf8ReachesTheResolver(): void
    {
        $this->expectException(ResolverException::class);
        $this->expectExceptionMessage('Invalid context');

        self::themeRegistry()->all(['scheme' => "\xff"]);
    }

    public function testRejectsAlternativeInputsWithoutResolverDocument(): void
    {
        $registry = Registries::fromArray(['a' => ['$type' => 'number', '$value' => 1]]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('no Resolver document is configured');

        $registry->all(['scheme' => 'dark']);
    }

    private static function brandRegistry(): TokenRegistry
    {
        return Registries::fromArray(['color' => ['brand' => ['$type' => 'color', '$value' => TokenValues::color()]]]);
    }

    /** @param array<string, string|int|float> $defaultInputs */
    private static function themeRegistry(array $defaultInputs = []): TokenRegistry
    {
        $loader = new ArrayDocumentLoader(['theme.resolver.json' => [
            'version' => '2025.10',
            'modifiers' => [
                'scheme' => [
                    'contexts' => [
                        'light' => [['theme' => ['$type' => 'number', '$value' => 1]]],
                        'dark' => [['theme' => ['$type' => 'number', '$value' => 2]]],
                    ],
                    'default' => 'light',
                ],
            ],
            'resolutionOrder' => [['$ref' => '#/modifiers/scheme']],
        ]]);

        return new TokenRegistry(new ConfiguredTokenResolver($loader, resolverPath: 'theme.resolver.json'), $defaultInputs);
    }

    /** @return TokenResolverInterface&object{calls: list<array<string, string|int|float>>} */
    private static function spyResolver(): TokenResolverInterface
    {
        return new class implements TokenResolverInterface {
            /** @var list<array<string, string|int|float>> */
            public array $calls = [];

            public function resolve(array $inputs): TokenResolution
            {
                $this->calls[] = $inputs;

                return new TokenResolution(['theme' => new NumberToken(1)]);
            }

            public function getPermutations(): array
            {
                return [];
            }

            public function getModifiers(): array
            {
                return [];
            }
        };
    }

    public function testAnExplicitInputReplacesADefaultSpelledWithAnotherCase(): void
    {
        self::assertSame(2, self::themeRegistry(['scheme' => 'light'])->get('theme', ['Scheme' => 'dark'])->getValue());
    }

    public function testAPropertyIsNotAToken(): void
    {
        $registry = Registries::fromArray([
            'color' => ['$description' => 'Colors', 'x' => ['$type' => 'number', '$value' => 1]],
            'space' => ['$root' => ['$type' => 'number', '$value' => 4]],
        ]);

        self::assertSame(4, $registry->get('space.$root')->getValue());

        $this->expectException(TokenNotFoundException::class);
        $this->expectExceptionMessage('names the DTCG property "$description"');
        $registry->get('color.$description');
    }
}
