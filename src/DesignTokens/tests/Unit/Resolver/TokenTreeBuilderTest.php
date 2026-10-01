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
use Symfony\UX\DesignTokens\Exception\ExceptionInterface;
use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Exception\RuntimeException;
use Symfony\UX\DesignTokens\Resolver\ArrayDocumentLoader;
use Symfony\UX\DesignTokens\Resolver\JsonDocumentLoader;
use Symfony\UX\DesignTokens\Resolver\ResolverSource;
use Symfony\UX\DesignTokens\Resolver\TokenResolution;
use Symfony\UX\DesignTokens\Resolver\TokenTreeBuilder;
use Symfony\UX\DesignTokens\Tests\Fixtures\TemporaryDirectory;
use Symfony\UX\DesignTokens\Tests\Fixtures\TokenValues;
use Symfony\UX\DesignTokens\Token\BorderToken;
use Symfony\UX\DesignTokens\Token\ColorToken;
use Symfony\UX\DesignTokens\Token\CubicBezierToken;
use Symfony\UX\DesignTokens\Token\DimensionToken;
use Symfony\UX\DesignTokens\Token\DurationToken;
use Symfony\UX\DesignTokens\Token\FontFamilyToken;
use Symfony\UX\DesignTokens\Token\FontWeightToken;
use Symfony\UX\DesignTokens\Token\GradientToken;
use Symfony\UX\DesignTokens\Token\NumberToken;
use Symfony\UX\DesignTokens\Token\ShadowToken;
use Symfony\UX\DesignTokens\Token\StrokeStyleToken;
use Symfony\UX\DesignTokens\Token\TransitionToken;
use Symfony\UX\DesignTokens\Token\TypographyToken;

#[CoversClass(TokenTreeBuilder::class)]
#[CoversClass(TokenResolution::class)]
final class TokenTreeBuilderTest extends TestCase
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

    public function testResolvesAllDtcgTypesAndComposites(): void
    {
        $result = new TokenTreeBuilder()->resolve([
            'color' => ['$type' => 'color', '$value' => TokenValues::color()],
            'dimension' => ['$type' => 'dimension', '$value' => TokenValues::dimension(16)],
            'duration' => ['$type' => 'duration', '$value' => TokenValues::dimension(300, 'ms')],
            'number' => ['$type' => 'number', '$value' => 42],
            'weight' => ['$type' => 'fontWeight', '$value' => 700],
            'family' => ['$type' => 'fontFamily', '$value' => ['Inter', 'sans-serif']],
            'bezier' => ['$type' => 'cubicBezier', '$value' => [0.4, -0.2, 0.2, 1.2]],
            'stroke' => ['$type' => 'strokeStyle', '$value' => ['dashArray' => [TokenValues::dimension(2)], 'lineCap' => 'round']],
            'gradient' => ['$type' => 'gradient', '$value' => [
                ['color' => TokenValues::color(), 'position' => 0],
                ['color' => TokenValues::color(1, 1, 1), 'position' => 1],
            ]],
            'border' => ['$type' => 'border', '$value' => TokenValues::border()],
            'shadow' => ['$type' => 'shadow', '$value' => TokenValues::shadow(inset: true)],
            'transition' => ['$type' => 'transition', '$value' => TokenValues::transition()],
            'typography' => ['$type' => 'typography', '$value' => TokenValues::typography()],
        ]);

        self::assertInstanceOf(ColorToken::class, $result['color']);
        self::assertInstanceOf(DimensionToken::class, $result['dimension']);
        self::assertInstanceOf(DurationToken::class, $result['duration']);
        self::assertInstanceOf(NumberToken::class, $result['number']);
        self::assertInstanceOf(FontWeightToken::class, $result['weight']);
        self::assertInstanceOf(FontFamilyToken::class, $result['family']);
        self::assertInstanceOf(CubicBezierToken::class, $result['bezier']);
        self::assertInstanceOf(StrokeStyleToken::class, $result['stroke']);
        self::assertInstanceOf(GradientToken::class, $result['gradient']);
        self::assertInstanceOf(BorderToken::class, $result['border']);
        self::assertInstanceOf(ShadowToken::class, $result['shadow']);
        self::assertStringStartsWith('inset ', (string) $result['shadow']);
        self::assertInstanceOf(TransitionToken::class, $result['transition']);
        self::assertInstanceOf(TypographyToken::class, $result['typography']);
    }

    public function testResolvesExactAliasesPointersComponentsArraysAndInfersAliasType(): void
    {
        $result = new TokenTreeBuilder()->resolve([
            'palette' => ['blue' => ['$type' => 'color', '$value' => TokenValues::color()]],
            'alias' => ['$value' => '{palette.blue}'],
            'dimension' => ['$type' => 'dimension', '$value' => [
                'value' => ['$ref' => '#/numbers/value/$value'],
                'unit' => ['$ref' => '#/units/pixel/$value'],
            ]],
            'numbers' => ['value' => ['$type' => 'number', '$value' => 12]],
            'units' => ['pixel' => ['$type' => 'fontFamily', '$value' => 'px']],
            'gradient' => ['$type' => 'gradient', '$value' => [[
                'color' => ['$ref' => '#/palette/blue/$value'],
                'position' => ['$ref' => '#/positions/$value/0'],
            ]]],
            'positions' => ['$type' => 'cubicBezier', '$value' => [0, 0, 1, 1]],
        ]);

        self::assertInstanceOf(ColorToken::class, $result['alias']);
        self::assertSame('12px', (string) $result['dimension']);
        self::assertSame(0, $result['gradient']->getValue()[0]['position']);
    }

    public function testJsonPointerSupportsRfc6901UriFragmentEncoding(): void
    {
        $result = new TokenTreeBuilder()->resolve([
            'space token' => ['$type' => 'number', '$value' => 12],
            'tilde~slash/name' => ['$type' => 'number', '$value' => 24],
            'encodedSpace' => ['$ref' => '#/space%20token/$value', '$type' => 'number'],
            'encodedEscapes' => ['$ref' => '#/tilde%7E0slash%7E1name/$value', '$type' => 'number'],
        ]);

        self::assertSame(12, $result['encodedSpace']->getValue());
        self::assertSame(24, $result['encodedEscapes']->getValue());
    }

    public function testResolvesRootExtendsMetadataAndDeprecationInheritance(): void
    {
        $extensions = ['org.example.tool' => ['source' => 'test']];
        $result = new TokenTreeBuilder()->resolve([
            'base' => [
                '$type' => 'dimension',
                '$deprecated' => 'Use space.new.',
                '$root' => ['$value' => TokenValues::dimension(4)],
                'small' => ['$value' => TokenValues::dimension(8)],
            ],
            'derived' => [
                '$extends' => '{base}',
                'small' => ['$type' => 'dimension', '$value' => TokenValues::dimension(12), '$deprecated' => false],
                'large' => ['$type' => 'dimension', '$value' => TokenValues::dimension(16), '$description' => 'Large space', '$extensions' => $extensions],
            ],
        ]);

        self::assertSame('4px', (string) $result['derived']['$root']);
        self::assertFalse($result['derived']['small']->isDeprecated());
        self::assertTrue($result['derived']['large']->isDeprecated());
        self::assertSame('Use space.new.', $result['derived']['large']->getDeprecationMessage());
        self::assertSame('Large space', $result['derived']['large']->getDescription());
        self::assertSame($extensions, $result['derived']['large']->getExtensions());
    }

    public function testGroupRefHasTheSameDeepMergeSemanticsAsExtends(): void
    {
        $result = new TokenTreeBuilder()->resolve([
            'base' => [
                '$type' => 'dimension',
                '$description' => 'Base group',
                'field' => [
                    'width' => ['$value' => TokenValues::dimension(12), '$description' => 'Inherited token'],
                    'gap' => ['$value' => TokenValues::dimension(4)],
                ],
            ],
            'derived' => [
                '$ref' => '#/base',
                '$description' => 'Derived group',
                'field' => [
                    'width' => ['$value' => TokenValues::dimension(24)],
                ],
            ],
            'copy' => ['$ref' => '#/base'],
        ]);

        self::assertSame('24px', (string) $result['derived']['field']['width']);
        self::assertNull($result['derived']['field']['width']->getDescription(), 'A local token replaces the complete inherited token.');
        self::assertSame('4px', (string) $result['derived']['field']['gap']);
        self::assertSame('12px', (string) $result['copy']['field']['width']);
    }

    public function testProcessesLocalRootInheritedAndNestedEntriesInNormativeOrder(): void
    {
        $result = new TokenTreeBuilder()->resolve([
            'base' => [
                '$type' => 'dimension',
                '$root' => ['$value' => TokenValues::dimension(1)],
                'inherited' => ['$value' => TokenValues::dimension(2)],
                'nested' => ['token' => ['$value' => TokenValues::dimension(3)]],
            ],
            'derived' => [
                '$extends' => '{base}',
                'local' => ['$value' => TokenValues::dimension(4)],
            ],
        ]);

        self::assertSame(['local', '$root', 'inherited', 'nested'], array_keys($result['derived']));
    }

    public function testResolutionDoesNotDestroyAuthoredReferenceExpressions(): void
    {
        $document = [
            'base' => ['$type' => 'number', '$value' => 1],
            'alias' => ['$value' => '{base}'],
        ];

        $result = new TokenTreeBuilder()->resolve($document);

        self::assertSame(1, $result['alias']->getValue());
        self::assertSame('{base}', $document['alias']['$value']);
    }

    public function testAliasTypeFollowsTheReferenceChainBeforeParentGroupType(): void
    {
        $result = new TokenTreeBuilder()->resolve([
            'base' => ['$type' => 'dimension', '$value' => TokenValues::dimension(8)],
            'intermediate' => ['$value' => '{base}'],
            'semantic' => [
                '$type' => 'color',
                'spacing' => ['$value' => '{intermediate}'],
            ],
            'pointer' => ['$ref' => '#/intermediate'],
        ]);

        self::assertInstanceOf(DimensionToken::class, $result['intermediate']);
        self::assertInstanceOf(DimensionToken::class, $result['semantic']['spacing']);
        self::assertInstanceOf(DimensionToken::class, $result['pointer']);
    }

    public function testMergesSourcesBeforeResolvingAliasesAndPreservesRelativeSourceUris(): void
    {
        $directory = $this->directory->path();
        $this->directory->write('base/color.tokens.json', [
            'palette' => ['blue' => ['$type' => 'color', '$value' => TokenValues::color()]],
        ]);
        $this->directory->write('theme/theme.tokens.json', [
            'semantic' => ['accent' => ['$type' => 'color', '$ref' => '../base/color.tokens.json#/palette/blue']],
        ]);

        $resolver = new TokenTreeBuilder(new JsonDocumentLoader());
        $result = $resolver->resolveSources([
            new ResolverSource(['base' => ['$type' => 'number', '$value' => 1]], $directory),
            new ResolverSource([
                'base' => ['$type' => 'number', '$value' => 2],
                'alias' => ['$value' => '{base}'],
            ], $directory),
            new ResolverSource([
                'semantic' => ['accent' => ['$type' => 'color', '$ref' => '../base/color.tokens.json#/palette/blue']],
            ], $directory.'/theme', 'theme/theme.tokens.json'),
        ]);

        self::assertSame(2, $result['alias']->getValue());
        self::assertInstanceOf(ColorToken::class, $result['semantic']['accent']);
    }

    /**
     * @param array<array-key, mixed>  $document
     * @param class-string<\Throwable> $exception
     */
    #[DataProvider('invalidDocumentProvider')]
    public function testRejectsAnInvalidDocument(array $document, string $exception, string $message): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        new TokenTreeBuilder()->resolve($document);
    }

    /** @return iterable<string, array{array<array-key, mixed>, class-string<\Throwable>, string}> */
    public static function invalidDocumentProvider(): iterable
    {
        $base = ['$type' => 'number', '$value' => 1];
        $size = ['size' => ['$type' => 'dimension', '$value' => TokenValues::dimension(4)]];

        yield 'name with a dot' => [
            ['bad.name' => $base],
            InvalidArgumentException::class, 'Invalid DTCG token or group name "bad.name" at "bad.name".',
        ];
        yield 'unknown token property' => [
            ['token' => [...$base, '$unknown' => true]],
            InvalidArgumentException::class, 'Unknown DTCG token property "$unknown" at "token".',
        ];
        yield 'unknown group property' => [
            ['$schema' => 'https://example.com/non-standard-schema.json'],
            InvalidArgumentException::class, 'Unknown DTCG group property "$schema" at "".',
        ];
        yield 'both $value and $ref' => [
            ['token' => [...$base, '$ref' => '#/other']],
            InvalidArgumentException::class, 'DTCG token "token" must define exactly one of $value or $ref.',
        ];
        yield 'token without type' => [
            ['token' => ['$value' => 1]],
            InvalidArgumentException::class, 'Every DTCG token must declare, inherit, or reference a $type at "token".',
        ];
        yield 'pointer into a value does not inherit the target token type' => [
            ['base' => ['$type' => 'color', '$value' => TokenValues::color()], 'red' => ['$ref' => '#/base/$value/components/0']],
            InvalidArgumentException::class, 'Every DTCG token must declare, inherit, or reference a $type at "red".',
        ];
        yield 'legacy string type' => [
            ['token' => ['$type' => 'string', '$value' => 'legacy']],
            InvalidArgumentException::class, '"string" at "token" is not a DTCG type. Expected one of: color, dimension, fontFamily, fontWeight, duration, cubicBezier, number, strokeStyle, border, transition, shadow, gradient, typography.',
        ];
        yield 'nested unknown type named by its token path' => [
            ['font' => ['family' => ['ui' => ['$type' => 'string', '$value' => 'Inter']]]],
            InvalidArgumentException::class, '"string" at "font.family.ui" is not a DTCG type.',
        ];
        yield 'nested value error named by its token path' => [
            ['color' => ['brand' => ['$type' => 'color', '$value' => ['colorSpace' => 'cmyk', 'components' => [0, 0, 0]]]]],
            InvalidArgumentException::class, 'Expected a supported color space for DTCG token at "color.brand.colorSpace".',
        ];
        yield 'nested unknown property named by its token path' => [
            ['a' => ['b' => [...$base, '$unknown' => true]]],
            InvalidArgumentException::class, 'Unknown DTCG token property "$unknown" at "a.b".',
        ];
        yield 'scalar entry' => [
            ['entry' => 'scalar'],
            InvalidArgumentException::class, 'DTCG entry "entry" must be a token or group object.',
        ];
        yield 'scalar $root' => [
            ['$root' => 'scalar'],
            InvalidArgumentException::class, 'DTCG $root at "" must be a token object.',
        ];
        yield 'child under a token' => [
            ['space' => ['$type' => 'dimension', '$value' => TokenValues::dimension(4), 'large' => ['$value' => TokenValues::dimension(8)]]],
            InvalidArgumentException::class, 'DTCG token "space" cannot hold the child "large": a node defining $value or $ref is a token, not a group.',
        ];

        yield 'non-string $description' => [
            ['token' => [...$base, '$description' => 42]],
            InvalidArgumentException::class, 'DTCG $description at "token" must be a string.',
        ];
        yield 'list $extensions' => [
            ['token' => [...$base, '$extensions' => []]],
            InvalidArgumentException::class, 'DTCG $extensions at "token" must be an object.',
        ];
        yield 'list $deprecated' => [
            ['token' => [...$base, '$deprecated' => []]],
            InvalidArgumentException::class, 'DTCG $deprecated at "token" must be a boolean or string.',
        ];

        yield 'missing curly target' => [
            ['alias' => ['$type' => 'number', '$value' => '{missing}']],
            RuntimeException::class, 'Reference target not found: "{missing}".',
        ];
        yield 'missing curly target without type' => [
            ['base' => $base, 'alias' => ['$value' => '{missing}']],
            RuntimeException::class, 'Reference target not found: "{missing}".',
        ];
        yield 'curly reference to a group' => [
            ['group' => ['token' => $base], 'alias' => ['$type' => 'number', '$value' => '{group}']],
            RuntimeException::class, 'Curly brace reference must target a token: "group".',
        ];
        yield 'curly reference to $value' => [
            ['base' => $base, 'alias' => ['$value' => '{base.$value}']],
            InvalidArgumentException::class, 'Invalid DTCG token or group name "$value" at "base.$value".',
        ];
        yield 'curly reference into $value' => [
            ['base' => $base, 'alias' => ['$type' => 'number', '$value' => '{base.$value.0}']],
            InvalidArgumentException::class, 'Invalid DTCG token or group name "$value" at "base.$value.0".',
        ];
        yield 'curly reference to $type' => [
            ['space' => ['$type' => 'dimension', 'small' => ['$value' => TokenValues::dimension(4)]], 'gap' => ['$type' => 'dimension', '$value' => '{space.$type}']],
            InvalidArgumentException::class, 'Invalid DTCG token or group name "$type" at "space.$type".',
        ];
        yield 'curly reference through an array' => [
            ['alias' => ['$type' => 'number', '$value' => '{data.items.0}'], 'data' => ['items' => [1]]],
            RuntimeException::class, 'Curly brace references cannot access array elements: "{data.items.0}".',
        ];
        yield 'curly reference into a token value' => [
            ['font' => ['stack' => ['$type' => 'fontFamily', '$value' => ['Inter', 'sans-serif']]], 'alias' => ['$type' => 'fontFamily', '$value' => '{font.stack.0}']],
            RuntimeException::class, 'Reference "{font.stack.0}" cannot address "0" inside the value of token "font.stack"; use a $ref JSON Pointer.',
        ];
        yield 'two-step curly cycle' => [
            ['a' => ['$type' => 'number', '$value' => '{b}'], 'b' => ['$type' => 'number', '$value' => '{a}']],
            RuntimeException::class, 'Circular reference detected: "curly:b -> curly:a -> curly:b".',
        ];
        yield 'three-step curly cycle names every reference' => [
            ['a' => ['$type' => 'number', '$value' => '{b}'], 'b' => ['$type' => 'number', '$value' => '{c}'], 'c' => ['$type' => 'number', '$value' => '{a}']],
            RuntimeException::class, 'Circular reference detected: "curly:b -> curly:c -> curly:a -> curly:b".',
        ];

        yield 'pointer without #' => [
            ['base' => $base, 'alias' => ['$type' => 'number', '$ref' => 'not-a-pointer']],
            RuntimeException::class, 'Invalid JSON Pointer reference: "not-a-pointer".',
        ];
        yield 'non-string pointer' => [
            ['token' => ['$type' => 'number', '$ref' => 42]],
            InvalidArgumentException::class, 'DTCG token $ref at "token" must be a JSON Pointer string.',
        ];
        yield 'missing pointer target' => [
            ['base' => $base, 'alias' => ['$ref' => '#/missing']],
            RuntimeException::class, 'Reference target not found: "#/missing".',
        ];
        yield 'pointer to its own value' => [
            ['token' => ['$type' => 'number', '$ref' => '#/token/$value']],
            RuntimeException::class, 'Reference target not found: "#/token/$value".',
        ];
        yield 'invalid pointer escape' => [
            ['base' => $base, 'alias' => ['$type' => 'number', '$ref' => '#/base~2/$value']],
            InvalidArgumentException::class, 'Invalid JSON Pointer escape in segment "base~2".',
        ];
        yield 'invalid percent-encoding' => [
            ['value' => ['$type' => 'number', '$value' => 12], 'invalid' => ['$ref' => '#/value%2/$value', '$type' => 'number']],
            InvalidArgumentException::class, 'Invalid percent-encoding in JSON Pointer fragment: "/value%2/$value".',
        ];
        yield 'external pointer without a loader' => [
            ['base' => $base, 'alias' => ['$type' => 'number', '$ref' => 'external.json#/base']],
            RuntimeException::class, 'Cannot load referenced token document "/external.json" without a loader.',
        ];
        yield 'whole-document pointer as a value' => [
            ['alias' => ['$type' => 'number', '$value' => ['$ref' => '#']]],
            RuntimeException::class, 'Circular reference detected: "pointer:# -> pointer:#".',
        ];

        yield 'non-string $extends' => [
            ['group' => ['$extends' => 42]],
            InvalidArgumentException::class, '$extends must be a reference string at "group".',
        ];
        yield 'plain word $extends' => [
            ['group' => ['$extends' => 'invalid']],
            InvalidArgumentException::class, '$extends must be a curly brace reference or a JSON Pointer, got "invalid".',
        ];
        yield 'dotted path $extends names both accepted forms' => [
            ['base' => $size, 'derived' => ['$extends' => 'base.size']],
            InvalidArgumentException::class, '$extends must be a curly brace reference or a JSON Pointer, got "base.size".',
        ];
        yield 'invalid pointer fragment in $extends' => [
            ['group' => ['$extends' => '#not-a-pointer']],
            InvalidArgumentException::class, 'Invalid JSON Pointer fragment: "not-a-pointer".',
        ];
        yield '$extends to a token' => [
            ['token' => $base, 'group' => ['$extends' => '{token}']],
            InvalidArgumentException::class, '$extends target must be a group: "{token}".',
        ];
        yield '$extends to an external reference' => [
            ['base' => ['$ref' => 'external.tokens.json#/group'], 'derived' => ['$extends' => '{base}']],
            InvalidArgumentException::class, '$extends target must be a group: "{base}".',
        ];
        yield '$extends into a token value' => [
            ['base' => $size, 'derived' => ['$extends' => '{base.size.inner}']],
            RuntimeException::class, 'Reference "{base.size.inner}" cannot address "inner" inside the value of token "base.size".',
        ];
        yield 'two-step $extends cycle' => [
            ['a' => ['$extends' => '{b}'], 'b' => ['$extends' => '{a}']],
            RuntimeException::class, 'Circular group extension detected: "a -> b -> a".',
        ];
        yield 'three-step $extends cycle names every group' => [
            ['groupA' => ['$extends' => '{groupB}'], 'groupB' => ['$extends' => '{groupC}'], 'groupC' => ['$extends' => '{groupA}']],
            RuntimeException::class, 'Circular group extension detected: "groupA -> groupB -> groupC -> groupA".',
        ];
    }

    public function testMergesFilesBeforeResolvingTheirReferences(): void
    {
        $base = $this->directory->write('base.tokens.json', [
            'group' => ['$type' => 'number', 'base' => ['$value' => 1]],
        ]);
        $alias = $this->directory->write('alias.tokens.json', [
            'group' => [
                'alias' => ['$ref' => 'base.tokens.json#/group/base'],
                'component' => ['$ref' => 'base.tokens.json#/group/base/$value'],
            ],
        ]);

        $resolver = new TokenTreeBuilder(new JsonDocumentLoader());
        $single = $resolver->resolveSources([self::fileSource($base)]);
        $merged = $resolver->resolveSources([self::fileSource($base), self::fileSource($alias)]);

        self::assertSame(1, $single['group']['base']->getValue());
        self::assertSame(1, $merged['group']['alias']->getValue());
        self::assertSame(1, $merged['group']['component']->getValue());
    }

    public function testResolveSourcesHandlesEmptyAndRejectsInvalidDescriptors(): void
    {
        $resolver = new TokenTreeBuilder();
        self::assertSame([], $resolver->resolveSources([]));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a ResolverSource');
        $resolver->resolveSources([42]);
    }

    public function testReportsWinningAndOverriddenSources(): void
    {
        $foundation = new ResolverSource([
            'color' => [
                'brand' => ['$type' => 'color', '$value' => TokenValues::color()],
                'text' => ['$type' => 'color', '$value' => TokenValues::color(0.1, 0.1, 0.1)],
            ],
        ], '/tokens', 'foundation.tokens.json');
        $brand = new ResolverSource([
            'color' => [
                'brand' => ['$type' => 'color', '$value' => TokenValues::color(0.9, 0.1, 0.1)],
            ],
        ], '/tokens', 'brand.tokens.json');

        $resolution = new TokenTreeBuilder()->resolveWithProvenance([$foundation, $brand]);

        self::assertSame('color(srgb 0.9 0.1 0.1)', (string) $resolution->getTokens()['color']['brand']);
        self::assertSame($brand, $resolution->getSource('color.brand'));
        self::assertSame([$foundation], $resolution->getOverrides('color.brand'));
        self::assertSame($foundation, $resolution->getSource('color.text'));
        self::assertSame([], $resolution->getOverrides('color.text'));
        self::assertNull($resolution->getSource('color.missing'));
        self::assertSame([], $resolution->getOverrides('color.missing'));
    }

    public function testReportsProvenanceForInlineSources(): void
    {
        $source = new ResolverSource([
            'space' => ['$type' => 'dimension', 'md' => ['$value' => TokenValues::dimension(16)]],
        ], '/tokens');

        $resolution = new TokenTreeBuilder()->resolveWithProvenance([$source]);

        self::assertSame($source, $resolution->getSource('space.md'));
    }

    public function testProvenanceTellsTwoFragmentsOfOneFileApart(): void
    {
        $light = new ResolverSource(['fg' => ['$type' => 'number', '$value' => 1]], '/x', 'theme.json#/light');
        $dark = new ResolverSource(['fg' => ['$type' => 'number', '$value' => 2]], '/x', 'theme.json#/dark');

        $resolution = new TokenTreeBuilder()->resolveWithProvenance([$light, $dark]);

        self::assertSame($dark, $resolution->getSource('fg'));
        self::assertSame([$light], $resolution->getOverrides('fg'));
    }

    public function testInfersTypesThroughInheritedCurlyAndPointerTargets(): void
    {
        $result = new TokenTreeBuilder()->resolve([
            'group' => [
                '$type' => 'number',
                'base' => ['$value' => 1],
            ],
            'curly' => ['$value' => '{group.base}'],
            'pointerToken' => ['$ref' => '#/group/base'],
            'pointerComponent' => ['$ref' => '#/group/base/$value'],
            'refChain' => ['$ref' => '#/pointerToken'],
        ]);

        self::assertInstanceOf(NumberToken::class, $result['curly']);
        self::assertInstanceOf(NumberToken::class, $result['pointerToken']);
        self::assertInstanceOf(NumberToken::class, $result['pointerComponent']);
        self::assertInstanceOf(NumberToken::class, $result['refChain']);
    }

    public function testAValueReferenceObjectTakesTheTypeOfItsTarget(): void
    {
        $tokens = new TokenTreeBuilder()->resolve([
            'base' => ['$type' => 'color', '$value' => TokenValues::color()],
            'alias' => ['$value' => ['$ref' => '#/base/$value']],
            'token' => ['$value' => ['$ref' => '#/base']],
        ]);

        self::assertInstanceOf(ColorToken::class, $tokens['alias']);
        self::assertSame('color(srgb 0.2 0.4 0.8)', (string) $tokens['alias']);
        self::assertInstanceOf(ColorToken::class, $tokens['token']);
    }

    public function testSupportsAbsoluteExternalPointers(): void
    {
        $external = $this->directory->write('external.tokens.json', '{"base":{"$type":"number","$value":3}}');
        $source = $this->directory->write('source.tokens.json', [
            'alias' => ['$ref' => $external.'#/base'],
        ]);

        $result = new TokenTreeBuilder(new JsonDocumentLoader())->resolveSources([self::fileSource($source)]);
        self::assertSame(3, $result['alias']->getValue());
    }

    public function testRecognizesMetadataOnlyGroups(): void
    {
        $result = new TokenTreeBuilder()->resolve([
            'base' => ['$description' => 'Metadata only'],
            'derived' => ['$extends' => '{base}'],
        ]);

        self::assertSame(['$description' => 'Metadata only'], $result['derived']);
        self::assertSame(['$description' => 'Metadata only'], $result['base']);
    }

    /** @param array<array-key, mixed> $document */
    #[DataProvider('referenceTargetProvider')]
    public function testAReferenceReachesItsTarget(array $document, string $path, string $expected): void
    {
        $token = new TokenTreeBuilder()->resolve($document);
        foreach (explode('.', $path) as $name) {
            $token = $token[$name];
        }

        self::assertSame($expected, (string) $token);
    }

    /** @return iterable<string, array{array<array-key, mixed>, string, string}> */
    public static function referenceTargetProvider(): iterable
    {
        $space = ['$type' => 'dimension', 'base' => ['$value' => TokenValues::dimension(16)]];
        $font = ['$type' => 'fontFamily', 'stack' => ['$value' => ['Inter', 'sans-serif']]];

        yield 'pointer to a whole token node' => [
            ['space' => $space, 'alias' => ['$ref' => '#/space/base']],
            'alias', '16px',
        ];
        yield 'pointer to an object value' => [
            ['space' => $space, 'alias' => ['$type' => 'dimension', '$ref' => '#/space/base/$value']],
            'alias', '16px',
        ];
        yield 'pointer to a member of an object value' => [
            ['space' => $space, 'alias' => ['$type' => 'number', '$ref' => '#/space/base/$value/value']],
            'alias', '16',
        ];
        yield 'pointer to an element of an array value' => [
            ['font' => $font, 'alias' => ['$type' => 'fontFamily', '$ref' => '#/font/stack/$value/0']],
            'alias', 'Inter',
        ];
        yield 'group pointer merges the target group' => [
            ['base' => ['$type' => 'dimension', 'small' => ['$value' => TokenValues::dimension(4)]], 'derived' => ['$ref' => '#/base']],
            'derived.small', '4px',
        ];
        yield 'curly reference to the reserved $root token' => [
            [
                'space' => ['$type' => 'dimension', '$root' => ['$value' => TokenValues::dimension(8)], 'small' => ['$value' => TokenValues::dimension(4)]],
                'gap' => ['$type' => 'dimension', '$value' => '{space.$root}'],
            ],
            'gap', '8px',
        ];
    }

    public function testSameDocumentPointerSeesTheMergedTree(): void
    {
        $tokens = new TokenTreeBuilder()->resolveSources([
            new ResolverSource(['brand' => ['$type' => 'number', '$value' => 1]], ''),
            new ResolverSource(['link' => ['$ref' => '#/brand']], ''),
            new ResolverSource(['brand' => ['$type' => 'number', '$value' => 2]], ''),
        ]);

        self::assertSame(2, $tokens['link']->getValue());
    }

    /**
     * @param array<string, array<array-key, mixed>> $files
     * @param list<ResolverSource>                   $sources
     */
    #[DataProvider('loadedReferenceProvider')]
    public function testResolvesAReferenceThroughTheLoader(array $files, array $sources, int $expected): void
    {
        $tokens = new TokenTreeBuilder(new ArrayDocumentLoader($files))->resolveSources($sources);

        self::assertSame($expected, $tokens['alias']->getValue());
    }

    /** @return iterable<string, array{array<string, array<array-key, mixed>>, list<ResolverSource>, int}> */
    public static function loadedReferenceProvider(): iterable
    {
        yield 'a pointer inside a referenced file stays in that file' => [
            ['/t/palette.json' => ['a' => ['$ref' => '#/b'], 'b' => ['$type' => 'number', '$value' => 7]]],
            [new ResolverSource(['alias' => ['$ref' => 'palette.json#/a'], 'b' => ['$type' => 'number', '$value' => 1]], '/t', '/t/main.json')],
            7,
        ];

        $theme = ['light' => ['fg' => ['$type' => 'number', '$value' => 1]], 'dark' => ['fg' => ['$type' => 'number', '$value' => 2]]];
        yield 'a fragment source does not hide its file' => [
            ['/t/theme.json' => $theme],
            [
                new ResolverSource($theme['light'], '/t', 'theme.json#/light'),
                new ResolverSource(['alias' => ['$ref' => 'theme.json#/dark/fg']], '/t'),
            ],
            2,
        ];
        yield 'a relative source keeps its references relative' => [
            ['palette.json' => ['c' => ['$type' => 'number', '$value' => 3]]],
            [new ResolverSource(['alias' => ['$ref' => 'palette.json#/c']], '', 'base.tokens.json')],
            3,
        ];
    }

    public function testNumericTopLevelNamesSurviveResolution(): void
    {
        $tokens = new TokenTreeBuilder()->resolveSources([
            new ResolverSource(json_decode('{"100":{"$type":"number","$value":1}}', true, 512, \JSON_THROW_ON_ERROR), ''),
        ]);

        self::assertSame([100], array_keys($tokens));
    }

    public function testAPartialDocumentLeavesOutTheTokensThatNeedAnotherSource(): void
    {
        $tokens = new TokenTreeBuilder()->resolvePartial(new ResolverSource([
            'color' => [
                '$type' => 'color',
                'local' => ['$value' => ['colorSpace' => 'srgb', 'components' => [0, 0, 1]]],
                'alias' => ['$value' => '{color.local}'],
                'brand' => ['$value' => '{color.palette.brand}'],
                'pointer' => ['$ref' => '#/color/palette/accent'],
                'chained' => ['$value' => '{color.brand}'],
            ],
            'button' => ['$extends' => '{component.base}', 'gap' => ['$type' => 'number', '$value' => 2]],
        ], ''));

        self::assertSame(['local', 'alias'], array_keys($tokens['color']));
        self::assertSame(['gap'], array_keys($tokens['button']));
    }

    /** @param array<string, mixed> $document */
    #[DataProvider('invalidPartialDocumentProvider')]
    public function testAPartialDocumentIsStillValidated(array $document, string $message): void
    {
        $this->expectException(ExceptionInterface::class);
        $this->expectExceptionMessage($message);

        new TokenTreeBuilder()->resolvePartial(new ResolverSource($document, ''));
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function invalidPartialDocumentProvider(): iterable
    {
        yield 'an invalid local value' => [['gap' => ['$type' => 'dimension', '$value' => '12px']], 'structured dimension'];
        yield 'an alias to a group it defines' => [['color' => ['palette' => ['a' => ['$type' => 'number', '$value' => 1]], 'alias' => ['$type' => 'number', '$value' => '{color.palette}']]], 'must target a token'];
    }

    public function testAPartialDocumentStillRejectsAMissingTargetInAnotherFile(): void
    {
        $this->directory->write('palette.tokens.json', '{"gap":{"$type":"number","$value":1}}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Reference target not found: "palette.tokens.json#/missing"');

        new TokenTreeBuilder(new JsonDocumentLoader())->resolvePartial(new ResolverSource(['gap' => ['$ref' => 'palette.tokens.json#/missing']], $this->directory->path(), $this->directory->path().'/theme.tokens.json'));
    }

    public function testAFullDocumentStillRejectsAMissingTarget(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Reference target not found: "{color.palette.brand}"');

        new TokenTreeBuilder()->resolveSources([new ResolverSource(['brand' => ['$type' => 'color', '$value' => '{color.palette.brand}']], '')]);
    }

    private static function fileSource(string $path): ResolverSource
    {
        return new ResolverSource(new JsonDocumentLoader()->load($path), \dirname($path), $path);
    }
}
