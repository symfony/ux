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
use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Exception\ResolverException;
use Symfony\UX\DesignTokens\Resolver\ArrayDocumentLoader;
use Symfony\UX\DesignTokens\Resolver\DocumentLoaderInterface;
use Symfony\UX\DesignTokens\Resolver\JsonDocumentLoader;
use Symfony\UX\DesignTokens\Resolver\ResolverDocument;
use Symfony\UX\DesignTokens\Resolver\ResolverSource;
use Symfony\UX\DesignTokens\Tests\Fixtures\ResolverDocuments;
use Symfony\UX\DesignTokens\Tests\Fixtures\TemporaryDirectory;

#[CoversClass(ResolverDocument::class)]
#[CoversClass(ResolverException::class)]
#[CoversClass(ResolverSource::class)]
final class ResolverDocumentTest extends TestCase
{
    private TemporaryDirectory $directory;

    protected function setUp(): void
    {
        $this->directory = new TemporaryDirectory();
    }

    protected function tearDown(): void
    {
        $this->directory->remove();
    }

    public function testResolvesOrderedSetsAndModifierContext(): void
    {
        self::assertCount(2, ResolverDocuments::sources(self::fixture('resolver-valid.resolver.json'), ['theme' => 'dark']));
    }

    public function testResolvesInlineSourcesAndDefaultModifierContext(): void
    {
        $document = $this->load([
            'sets' => [],
            'modifiers' => [
                'theme' => [
                    'contexts' => [
                        'light' => [['$type' => 'string', '$value' => 'light']],
                        'dark' => [['$type' => 'string', '$value' => 'dark']],
                    ],
                    'default' => 'dark',
                ],
            ],
            'resolutionOrder' => [['$ref' => '#/modifiers/theme']],
        ]);

        self::assertSame('dark', ResolverDocuments::sources($document)[0]['$value'] ?? null);
    }

    /**
     * @param array<string, mixed>     $inputs
     * @param class-string<\Throwable> $exception
     */
    #[DataProvider('invalidFixtureInputProvider')]
    public function testRejectsInvalidInputsForAFixture(string $fixture, array $inputs, string $exception, string $message): void
    {
        $document = self::fixture($fixture);

        $this->expectException($exception);
        $this->expectExceptionMessage($message);
        ResolverDocuments::sources($document, $inputs);
    }

    /** @return iterable<string, array{string, array<string, mixed>, class-string<\Throwable>, string}> */
    public static function invalidFixtureInputProvider(): iterable
    {
        yield 'unknown context' => ['resolver-valid.resolver.json', ['theme' => 'unknown'], ResolverException::class, 'Invalid context "unknown" for modifier "theme".'];
        yield 'modifier without default left out' => ['resolver-complete.resolver.json', [], ResolverException::class, 'Missing required modifier "density".'];
    }

    public function testLoadsExternalSourcesWithAndWithoutFragments(): void
    {
        $this->directory->write('tokens.json', [
            'group' => [
                'token' => ['$type' => 'string', '$value' => 'hello'],
            ],
        ]);
        $document = $this->load([
            'sets' => [],
            'modifiers' => [],
            'resolutionOrder' => [self::set([['$ref' => 'tokens.json'], ['$ref' => 'tokens.json#/group']])],
        ]);

        $sources = ResolverDocuments::sources($document);

        self::assertCount(2, $sources);
        self::assertArrayHasKey('group', $sources[0]);
        self::assertArrayHasKey('token', $sources[1]);
    }

    public function testPreservesSourceReferenceBasePath(): void
    {
        $this->directory->write('nested/tokens.json', '{"token":{"$type":"string","$value":"ok"}}');

        $sources = $this->load(['resolutionOrder' => [self::set([['$ref' => 'nested/tokens.json']])]])->sourceDescriptors();

        self::assertCount(1, $sources);
        self::assertSame($this->directory->path('nested'), $sources[0]->basePath);
        self::assertSame('nested/tokens.json', $sources[0]->uri);
    }

    public function testSupportsVersionAndEnumeratesPermutations(): void
    {
        $document = self::fixture('resolver-complete.resolver.json');

        self::assertSame('2025.10', ResolverDocument::VERSION);
        self::assertCount(4, $document->getPermutations());
        self::assertSame([
            ['theme' => 'light', 'density' => 'comfortable'],
            ['theme' => 'light', 'density' => 'compact'],
            ['theme' => 'dark', 'density' => 'comfortable'],
            ['theme' => 'dark', 'density' => 'compact'],
        ], $document->getPermutations());
    }

    public function testPermutationsKeepNumericModifierNames(): void
    {
        $document = new ResolverDocument(json_decode('{
            "version": "2025.10",
            "modifiers": {
                "1": {"contexts": {"a": [], "b": []}, "default": "a"},
                "2": {"contexts": {"a": [], "b": []}, "default": "a"}
            },
            "resolutionOrder": [{"$ref": "#/modifiers/1"}, {"$ref": "#/modifiers/2"}]
        }', true, 512, \JSON_THROW_ON_ERROR));

        $permutations = $document->getPermutations();

        self::assertSame([
            [1 => 'a', 2 => 'a'],
            [1 => 'a', 2 => 'b'],
            [1 => 'b', 2 => 'a'],
            [1 => 'b', 2 => 'b'],
        ], $permutations);
    }

    public function testExternalFragmentReferencesKeepNumericTokenNames(): void
    {
        $this->directory->write('palette.json', '{"grey": {"$type": "number", "50": {"$value": 1}, "100": {"$value": 2}}}');

        $document = new ResolverDocument(json_decode('{
            "version": "2025.10",
            "sets": {"grey": {"sources": [{"$ref": "palette.json#/grey"}]}},
            "resolutionOrder": [{"$ref": "#/sets/grey"}]
        }', true, 512, \JSON_THROW_ON_ERROR), $this->directory->path());

        self::assertSame(['$type', 50, 100], array_keys($document->sourceDescriptors()[0]->tokens));
    }

    public function testDescribesModifierContextsDefaultsAndResolutionPlan(): void
    {
        $document = self::fixture('resolver-complete.resolver.json');

        self::assertSame([
            'theme' => ['contexts' => ['light', 'dark'], 'default' => 'light'],
            'density' => ['contexts' => ['comfortable', 'compact'], 'default' => null],
        ], $document->getModifiers());

        $plan = $document->resolutionPlan(['theme' => 'dark', 'density' => 'compact']);
        self::assertSame([
            ['type' => 'set', 'name' => 'foundation', 'context' => null],
            ['type' => 'modifier', 'name' => 'theme', 'context' => 'dark'],
            ['type' => 'modifier', 'name' => 'density', 'context' => 'compact'],
            ['type' => 'set', 'name' => 'component', 'context' => null],
        ], array_map(static fn (array $selection): array => array_diff_key($selection, ['sources' => true]), $plan));
        self::assertCount(1, $plan[0]['sources']);
        self::assertSame(2, $plan[1]['sources'][0]->tokens['color']['surface']['$value'] ?? null);
        self::assertSame(4, $plan[2]['sources'][0]->tokens['space']['control']['$value']['value'] ?? null);
    }

    public function testAppliesSourcesAndResolutionOrderWithLastValueWinning(): void
    {
        $resolved = ResolverDocuments::merged(self::fixture('resolver-complete.resolver.json'), ['THEME' => 'DARK', 'Density' => 'COMPACT']);

        self::assertSame(['surface' => ['$type' => 'number', '$value' => 2]], $resolved['color'] ?? null);
        self::assertSame(['control' => ['$type' => 'dimension', '$value' => ['value' => 4, 'unit' => 'px']]], $resolved['space'] ?? null);
        self::assertSame(['precedence' => ['$type' => 'number', '$value' => 3]], $resolved['component'] ?? null);
    }

    public function testUsesDefaultsAndAllowsAnEmptyContextSourceList(): void
    {
        $document = $this->load([
            'resolutionOrder' => [[
                'type' => 'modifier',
                'name' => 'debug',
                'contexts' => [
                    'false' => [],
                    'true' => [['debug' => ['$type' => 'number', '$value' => 1]]],
                ],
                'default' => 'false',
            ]],
        ]);

        self::assertSame([], ResolverDocuments::sources($document));
        self::assertSame([['debug' => ['$type' => 'number', '$value' => 1]]], ResolverDocuments::sources($document, ['debug' => 'true']));
    }

    public function testReportsAllInvalidInputsTogether(): void
    {
        $document = self::fixture('resolver-complete.resolver.json');

        try {
            ResolverDocuments::sources($document, ['theme' => 'blue', 'unknown' => 'value', 'density' => true]);
            self::fail('Invalid resolver inputs should be rejected.');
        } catch (ResolverException $exception) {
            self::assertSame([
                'Invalid context "blue" for modifier "theme".',
                'Unknown modifier "unknown".',
                'Input "density" must be a string or a number.',
                'Missing required modifier "density".',
            ], $exception->getErrors());
        }
    }

    public function testRejectsAnInputProvidedTwiceWithDifferentCasing(): void
    {
        $document = self::document([
            'modifiers' => ['theme' => ['contexts' => ['light' => [], 'dark' => []], 'default' => 'light']],
            'resolutionOrder' => [['$ref' => '#/modifiers/theme']],
        ]);

        $this->expectException(ResolverException::class);
        $this->expectExceptionMessage('provided more than once');
        ResolverDocuments::sources($document, ['Theme' => 'light', 'theme' => 'dark']);
    }

    public function testModifierMayReferenceASet(): void
    {
        $document = $this->load([
            'sets' => ['base' => ['sources' => [['base' => ['$type' => 'number', '$value' => 1]]]]],
            'modifiers' => ['theme' => [
                'contexts' => [
                    'light' => [['$ref' => '#/sets/base'], ['theme' => ['$type' => 'string', '$value' => 'light']]],
                    'dark' => [['theme' => ['$type' => 'string', '$value' => 'dark']]],
                ],
            ]],
            'resolutionOrder' => [['$ref' => '#/modifiers/theme']],
        ]);

        $resolved = ResolverDocuments::merged($document, ['theme' => 'light']);
        self::assertSame(['$type' => 'number', '$value' => 1], $resolved['base'] ?? null);
        self::assertSame(['$type' => 'string', '$value' => 'light'], $resolved['theme'] ?? null);
    }

    public function testResolvesReferenceOverridesShallowly(): void
    {
        $document = $this->load([
            '$defs' => [
                'source' => [
                    'token' => ['$type' => 'string', '$value' => 'base'],
                    'nested' => ['kept' => true],
                    'list' => ['base'],
                ],
            ],
            'resolutionOrder' => [self::set([[
                '$ref' => '#/$defs/source',
                'token' => ['$type' => 'string', '$value' => 'override'],
                'nested' => ['replaced' => true],
                'list' => ['override'],
            ]])],
        ]);

        $resolved = ResolverDocuments::merged($document);
        self::assertSame(['$type' => 'string', '$value' => 'override'], $resolved['token'] ?? null);
        self::assertSame(['replaced' => true], $resolved['nested'] ?? null);
        self::assertSame(['override'], $resolved['list'] ?? null);
    }

    /**
     * @param array<string, mixed>     $data
     * @param class-string<\Throwable> $exception
     */
    #[DataProvider('invalidDocumentProvider')]
    public function testRejectsAnInvalidDocument(array $data, string $exception, string $message): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        ResolverDocuments::sources(self::document($data));
    }

    /** @return iterable<string, array{array<string, mixed>, class-string<\Throwable>, string}> */
    public static function invalidDocumentProvider(): iterable
    {
        $order = ['resolutionOrder' => [self::set([])]];
        $empty = ['sets' => [], 'modifiers' => [], '$defs' => [], ...$order];
        $sources = [['size' => ['$type' => 'dimension', '$value' => ['value' => 1, 'unit' => 'px']]]];

        yield 'other version' => [
            ['version' => '2024.01', 'resolutionOrder' => [['sources' => []]]],
            InvalidArgumentException::class, 'A DTCG Resolver document must declare version "2025.10".',
        ];
        yield 'empty resolution order' => [
            ['resolutionOrder' => []],
            InvalidArgumentException::class, 'A DTCG Resolver document must define a non-empty resolutionOrder array.',
        ];
        yield 'unknown property' => [
            [...$empty, 'unknown' => true],
            InvalidArgumentException::class, 'Resolver document contains unsupported property "unknown".',
        ];
        yield 'integer name' => [
            ['name' => 42, ...$order],
            InvalidArgumentException::class, 'Resolver property "name" must be a string.',
        ];
        yield 'list name' => [
            [...$empty, 'name' => []],
            InvalidArgumentException::class, 'Resolver property "name" must be a string.',
        ];
        yield 'list description' => [
            [...$empty, 'description' => []],
            InvalidArgumentException::class, 'Resolver property "description" must be a string.',
        ];
        yield 'boolean $schema' => [
            [...$empty, '$schema' => false],
            InvalidArgumentException::class, 'Resolver property "$schema" must be a string.',
        ];
        yield 'sets as a list' => [
            [...$empty, 'sets' => [[]]],
            InvalidArgumentException::class, 'Resolver property "sets" must be an object.',
        ];
        yield 'set with an empty name' => [
            ['sets' => ['' => ['sources' => []]], ...$order],
            InvalidArgumentException::class, 'Every resolver set must be a named object.',
        ];
        yield 'modifier with an empty name' => [
            ['modifiers' => ['' => ['contexts' => ['a' => [], 'b' => []]]], ...$order],
            InvalidArgumentException::class, 'Every resolver modifier must be a named object.',
        ];
        yield 'set with a non-string description' => [
            ['sets' => ['base' => ['sources' => [], 'description' => 42]], 'resolutionOrder' => [['$ref' => '#/sets/base']]],
            InvalidArgumentException::class, 'Set "base" property "description" must be a string.',
        ];

        yield 'modifiers differing only by case' => [
            [
                'modifiers' => [
                    'Theme' => ['contexts' => ['light' => [], 'dark' => []], 'default' => 'light'],
                    'theme' => ['contexts' => ['compact' => [], 'comfortable' => []], 'default' => 'compact'],
                ],
                'resolutionOrder' => [['$ref' => '#/modifiers/Theme'], ['$ref' => '#/modifiers/theme']],
            ],
            InvalidArgumentException::class, 'Modifier names "Theme" and "theme" differ only by case.',
        ];
        yield 'contexts differing only by case' => [
            [
                'modifiers' => ['theme' => ['contexts' => ['Light' => [], 'light' => []], 'default' => 'Light']],
                'resolutionOrder' => [['$ref' => '#/modifiers/theme']],
            ],
            InvalidArgumentException::class, 'Context names "Light" and "light" of modifier "theme" differ only by case.',
        ];

        yield 'scalar item' => [
            ['resolutionOrder' => [42]],
            InvalidArgumentException::class, 'resolutionOrder[0] must be an object.',
        ];
        yield 'non-string reference' => [
            ['resolutionOrder' => [['$ref' => 42]]],
            InvalidArgumentException::class, 'The $ref in resolutionOrder[0] must be a non-empty string.',
        ];
        yield 'inline type absent' => [
            ['resolutionOrder' => [['name' => 'x', 'sources' => []]]],
            InvalidArgumentException::class, 'Inline resolutionOrder item 0 must declare type "set" or "modifier".',
        ];
        yield 'inline set with non-list sources' => [
            ['resolutionOrder' => [['type' => 'set', 'name' => 'x', 'sources' => 'tokens.json']]],
            InvalidArgumentException::class, 'Set "x" must declare a sources array.',
        ];
        yield 'inline name absent' => [
            ['resolutionOrder' => [['type' => 'set', 'sources' => []]]],
            InvalidArgumentException::class, 'Inline resolutionOrder item 0 must declare a non-empty name.',
        ];
        yield 'inline name duplicate' => [
            ['resolutionOrder' => [self::set([], 'x'), self::set([], 'x')]],
            InvalidArgumentException::class, 'Resolution order name "x" is duplicated.',
        ];
        yield 'empty contexts' => [
            ['resolutionOrder' => [['type' => 'modifier', 'name' => 'mode', 'contexts' => []]]],
            InvalidArgumentException::class, 'Modifier "mode" must declare a non-empty contexts object.',
        ];
        yield 'scalar contexts' => [
            ['resolutionOrder' => [['type' => 'modifier', 'name' => 'theme', 'contexts' => 'dark']]],
            InvalidArgumentException::class, 'Modifier "theme" must declare a non-empty contexts object.',
        ];
        yield 'default outside the contexts' => [
            ['resolutionOrder' => [['type' => 'modifier', 'name' => 'mode', 'contexts' => ['a' => [], 'b' => []], 'default' => 'c']]],
            InvalidArgumentException::class, 'Default context for modifier "mode" must match a context key.',
        ];

        yield 'reference into the resolution order' => [
            ['resolutionOrder' => [['$ref' => '#/resolutionOrder/0']]],
            InvalidArgumentException::class, 'Resolver references must not point into resolutionOrder: "#/resolutionOrder/0".',
        ];
        $forbidden = [
            'sets' => ['tokens' => ['sources' => []]],
            'modifiers' => ['mode' => ['contexts' => ['a' => [], 'b' => []]]],
        ];
        yield 'set to modifier' => [
            [...$forbidden, 'resolutionOrder' => [self::set([['$ref' => '#/modifiers/mode']], 'inline')]],
            InvalidArgumentException::class, 'Sets must not reference modifiers: "#/modifiers/mode".',
        ];
        yield 'modifier to modifier' => [
            [...$forbidden, 'resolutionOrder' => [['type' => 'modifier', 'name' => 'inline', 'contexts' => ['a' => [['$ref' => '#/modifiers/mode']], 'b' => []]]]],
            InvalidArgumentException::class, 'Modifiers must not reference modifiers: "#/modifiers/mode".',
        ];
        yield 'set to resolution order' => [
            [...$forbidden, 'resolutionOrder' => [self::set([['$ref' => '#/resolutionOrder/0']], 'inline')]],
            InvalidArgumentException::class, 'Resolver references must not point into resolutionOrder: "#/resolutionOrder/0".',
        ];
        yield 'modifier to resolution order' => [
            [...$forbidden, 'resolutionOrder' => [['type' => 'modifier', 'name' => 'inline', 'contexts' => ['a' => [['$ref' => '#/resolutionOrder/0']], 'b' => []]]]],
            InvalidArgumentException::class, 'Resolver references must not point into resolutionOrder: "#/resolutionOrder/0".',
        ];
        yield 'circular set references' => [
            [
                'sets' => ['a' => ['sources' => [['$ref' => '#/sets/b']]], 'b' => ['sources' => [['$ref' => '#/sets/a']]]],
                'resolutionOrder' => [['$ref' => '#/sets/a']],
            ],
            InvalidArgumentException::class, 'Circular resolver reference detected: #/resolutionOrder/0 -> #/sets/b -> #/sets/a -> #/sets/b.',
        ];
        yield 'invalid pointer escape' => [
            ['sets' => ['a~b' => ['sources' => []]], 'resolutionOrder' => [['$ref' => '#/sets/a~2b']]],
            InvalidArgumentException::class, 'Invalid JSON Pointer escape in segment "a~2b".',
        ];
        yield 'broken pointer in a set left out of the resolution order' => [
            [
                'sets' => ['used' => ['sources' => $sources], 'unused' => ['sources' => [['$ref' => '#/sets/missing']]]],
                'resolutionOrder' => [['$ref' => '#/sets/used']],
            ],
            InvalidArgumentException::class, 'Resolver pointer not found: "#/sets/missing".',
        ];
        yield 'broken pointer in a context no input selects' => [
            [
                'modifiers' => ['scheme' => ['contexts' => ['light' => $sources, 'dark' => [['$ref' => '#/sets/missing']]], 'default' => 'light']],
                'resolutionOrder' => [['$ref' => '#/modifiers/scheme']],
            ],
            InvalidArgumentException::class, 'Resolver pointer not found: "#/sets/missing".',
        ];
    }

    /**
     * @param string|array<array-key, mixed> $contents
     * @param class-string<\Throwable>       $exception
     */
    #[DataProvider('invalidExternalSourceProvider')]
    public function testRejectsAnInvalidExternalSource(string|array $contents, string $reference, string $exception, string $message): void
    {
        $this->directory->write('tokens.json', $contents);
        $document = $this->load(['resolutionOrder' => [self::set([['$ref' => $reference]], 'external')]]);

        $this->expectException($exception);
        $this->expectExceptionMessage($message);
        ResolverDocuments::sources($document);
    }

    /** @return iterable<string, array{string|array<array-key, mixed>, string, class-string<\Throwable>, string}> */
    public static function invalidExternalSourceProvider(): iterable
    {
        yield 'missing pointer' => [
            '{"group":{}}', 'tokens.json#/missing',
            InvalidArgumentException::class, 'Resolver pointer not found: "tokens.json#/missing".',
        ];
        yield 'chained reference to a modifier' => [
            ['source' => ['$ref' => '#/modifiers/theme'], 'modifiers' => ['theme' => ['contexts' => ['a' => [], 'b' => []]]]], 'tokens.json#/source',
            InvalidArgumentException::class, 'Sets must not reference modifiers: "#/modifiers/theme".',
        ];
        yield 'nested set with a scalar source' => [
            '{"set":{"sources":[42]}}', 'tokens.json#/set',
            InvalidArgumentException::class, 'Token source 0 must be an object',
        ];
    }

    public function testSupportsWholeDocumentAndAbsoluteSourceReferences(): void
    {
        $tokensPath = $this->directory->write('tokens.json', '{"token":{"$type":"number","$value":1}}');

        $sources = $this->load([
            '$defs' => ['whole' => ['$ref' => '#']],
            'resolutionOrder' => [self::set([['$ref' => $tokensPath]], 'absolute')],
        ])->sourceDescriptors();

        self::assertSame($this->directory->path(), $sources[0]->basePath);
        self::assertSame(1, $sources[0]->tokens['token']['$value'] ?? null);
    }

    public function testExpandsWholeDocumentReferences(): void
    {
        $whole = self::document([
            'sets' => ['base' => ['sources' => [['$ref' => '#']]]],
            'resolutionOrder' => [['$ref' => '#/sets/base']],
        ]);

        self::assertSame('2025.10', ResolverDocuments::sources($whole)[0]['version'] ?? null);
    }

    /** @param array<string, mixed> $document */
    #[DataProvider('numericNameProvider')]
    public function testAcceptsNamesThatLookLikeIntegers(array $document): void
    {
        self::assertNotSame([], ResolverDocuments::merged(self::document($document)));
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function numericNameProvider(): iterable
    {
        $sources = [['size' => ['$type' => 'dimension', '$value' => ['value' => 1, 'unit' => 'px']]]];

        yield 'context' => [[
            'modifiers' => ['breakpoint' => [
                'contexts' => ['320' => $sources, 'wide' => $sources],
                'default' => '320',
            ]],
            'resolutionOrder' => [['$ref' => '#/modifiers/breakpoint']],
        ]];
        yield 'set' => [[
            'sets' => ['2024' => ['sources' => $sources]],
            'resolutionOrder' => [['$ref' => '#/sets/2024']],
        ]];
        yield 'modifier' => [[
            'modifiers' => ['320' => ['contexts' => ['a' => $sources, 'b' => $sources], 'default' => 'a']],
            'resolutionOrder' => [['$ref' => '#/modifiers/320']],
        ]];
    }

    public function testExternalSourcesAreReadOnceThroughTheLoader(): void
    {
        $loader = new class implements DocumentLoaderInterface {
            /** @var list<string> */
            public array $uris = [];

            public function load(string $uri): array
            {
                $this->uris[] = $uri;

                return ['a' => ['$type' => 'number', '$value' => 1]];
            }
        };
        $document = self::document(['resolutionOrder' => [self::set([['$ref' => 'sets/base.json']])]], '/project/tokens', $loader);

        $document->sourceDescriptors();
        $document->sourceDescriptors();

        self::assertSame(['/project/tokens/sets/base.json'], $loader->uris);
        self::assertSame(['/project/tokens/sets/base.json'], $document->loadedUris());
    }

    public function testAnEmptyBasePathKeepsReferencesRelative(): void
    {
        $loader = new ArrayDocumentLoader([
            'sub/a.json' => ['sources' => [['$ref' => 'b.json']]],
            'sub/b.json' => ['b' => ['$type' => 'number', '$value' => 1]],
        ]);

        $sources = self::document(['resolutionOrder' => [self::set([['$ref' => 'sub/a.json']])]], '', $loader)->sourceDescriptors();

        self::assertSame(1, $sources[0]->tokens['b']['$value']);
    }

    public function testNumericInputsSelectTheirContext(): void
    {
        $document = new ResolverDocument(json_decode('{
            "version": "2025.10",
            "modifiers": {"breakpoint": {"contexts": {"320": [{"a": {"$type": "number", "$value": 1}}], "768": [{"a": {"$type": "number", "$value": 2}}]}, "default": "320"}},
            "resolutionOrder": [{"$ref": "#/modifiers/breakpoint"}]
        }', true, 512, \JSON_THROW_ON_ERROR));

        self::assertSame(2, $document->sourceDescriptors(['breakpoint' => 768])[0]->tokens['a']['$value']);
    }

    /** @param array<string, mixed> $data */
    private function load(array $data): ResolverDocument
    {
        return self::document($data, $this->directory->path());
    }

    /** @param array<string, mixed> $data */
    private static function document(array $data, string $basePath = '', ?DocumentLoaderInterface $loader = null): ResolverDocument
    {
        return new ResolverDocument(['version' => ResolverDocument::VERSION, ...$data], $basePath, $loader);
    }

    /**
     * @param list<array<array-key, mixed>> $sources
     *
     * @return array{type: 'set', name: string, sources: list<array<array-key, mixed>>}
     */
    private static function set(array $sources, string $name = 'base'): array
    {
        return ['type' => 'set', 'name' => $name, 'sources' => $sources];
    }

    private static function fixture(string $name): ResolverDocument
    {
        $path = \dirname(__DIR__, 2).'/Fixtures/dtcg/'.$name;

        return new ResolverDocument(new JsonDocumentLoader()->load($path), \dirname($path));
    }
}
