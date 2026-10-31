<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Generator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Generator\DtcgGenerator;
use Symfony\UX\DesignTokens\Resolver\TokenTreeBuilder;

#[CoversClass(DtcgGenerator::class)]
final class DtcgGeneratorTest extends TestCase
{
    private DtcgGenerator $generator;
    private TokenTreeBuilder $resolver;

    protected function setUp(): void
    {
        $this->generator = new DtcgGenerator();
        $this->resolver = new TokenTreeBuilder();
    }

    public function testExportsNativeValuesMetadataEmptyGroupsAndNumericNames(): void
    {
        $resolved = $this->resolver->resolve([
            'empty' => [],
            'color' => [
                '100' => [
                    '$type' => 'color',
                    '$value' => ['colorSpace' => 'display-p3', 'components' => [0.1, 0.2, 0.3], 'alpha' => 0.5],
                    '$description' => '',
                    '$extensions' => ['org.example' => ['source' => 'brand']],
                    '$deprecated' => 'Use color.200.',
                ],
            ],
            'spacing' => [
                '$type' => 'dimension',
                'md' => ['$value' => ['value' => 1, 'unit' => 'rem']],
            ],
        ]);

        $json = $this->generator->generate($resolved);
        $document = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame([], $document['empty']);
        self::assertStringContainsString('"empty": {}', $json);
        self::assertSame('color', $document['color']['100']['$type']);
        self::assertSame('display-p3', $document['color']['100']['$value']['colorSpace']);
        self::assertSame('', $document['color']['100']['$description']);
        self::assertSame(['org.example' => ['source' => 'brand']], $document['color']['100']['$extensions']);
        self::assertSame('Use color.200.', $document['color']['100']['$deprecated']);
        self::assertSame(
            ['value' => 1, 'unit' => 'rem'],
            $document['spacing']['md']['$value'],
        );
        self::assertStringEndsWith("\n", $json);
    }

    public function testMaterializesReferencesAndRoundTripsSemantically(): void
    {
        $source = [
            'base' => ['$type' => 'dimension', '$value' => ['value' => 8, 'unit' => 'px']],
            'alias' => ['$value' => '{base}', '$description' => 'Resolved alias'],
            'gradient' => ['$type' => 'gradient', '$value' => [
                ['color' => ['colorSpace' => 'srgb', 'components' => [0, 0, 0]], 'position' => 0],
                ['color' => ['colorSpace' => 'srgb', 'components' => [1, 1, 1]], 'position' => 1],
            ]],
        ];

        $first = $this->generator->generate($this->resolver->resolve($source));
        $exported = json_decode($first, true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame(['value' => 8, 'unit' => 'px'], $exported['alias']['$value']);
        self::assertSame('dimension', $exported['alias']['$type']);
        self::assertSame($first, $this->generator->generate($this->resolver->resolve($exported)));
    }

    public function testExportsAnEmptyDocumentAsAJsonObject(): void
    {
        self::assertSame("{}\n", $this->generator->generate([]));
    }

    public function testKeepsGroupDescriptionAndExtensions(): void
    {
        $document = json_decode($this->generator->generate($this->resolver->resolve([
            'group' => [
                '$type' => 'number',
                '$description' => 'Documented group',
                '$extensions' => ['org.example.tool-a' => ['note' => 'keep me']],
                'a' => ['$value' => 1],
            ],
            'empty' => ['$description' => 'Metadata only'],
        ])), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame('Documented group', $document['group']['$description']);
        self::assertSame(['note' => 'keep me'], $document['group']['$extensions']['org.example.tool-a']);
        self::assertSame('Metadata only', $document['empty']['$description']);
    }
}
