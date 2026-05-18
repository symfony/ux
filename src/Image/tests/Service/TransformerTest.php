<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Service\Transformer;

class TransformerTest extends TestCase
{
    private Transformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new Transformer([
            'sm' => 640,
            'md' => 768,
            'lg' => 1024,
            'xl' => 1280,
            '2xl' => 1536,
        ]);
    }

    #[DataProvider('provideWidthParsing')]
    public function testParseWidth(string $input, array $expected): void
    {
        $result = $this->transformer->parseWidth($input);

        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $result);
            $this->assertEquals($value['value'], $result[$key]['value'], "Value mismatch for key: $key");
            $this->assertEquals($value['vw'], $result[$key]['vw'], "VW mismatch for key: $key");
        }
    }

    public static function provideWidthParsing(): array
    {
        return [
            'simple fixed' => [
                '800',
                ['default' => ['value' => 800, 'vw' => '0']],
            ],
            'viewport width' => [
                '100vw',
                ['default' => ['value' => 640, 'vw' => '100']],
            ],
            'responsive with breakpoints' => [
                '100vw sm:50vw md:400',
                [
                    'default' => ['value' => 640, 'vw' => '100'],
                    'sm' => ['value' => 640, 'vw' => '100'],
                    'md' => ['value' => 400, 'vw' => '0'],
                ],
            ],
        ];
    }

    public function testGetSizesReturns100vw(): void
    {
        $widths = $this->transformer->parseWidth('100vw');
        $this->assertEquals('100vw', $this->transformer->getSizes($widths));
    }

    public function testGetDensityBasedWidths(): void
    {
        $result = $this->transformer->getDensityBasedWidths(200, 'x1 x2');
        $this->assertEquals([200, 400], $result);
    }

    public function testParseRatio(): void
    {
        $result = $this->transformer->parseRatio('sm:1:1 md:16:9');
        $this->assertEquals(['sm' => '1:1', 'md' => '16:9'], $result);
    }

    public function testCascadeRatios(): void
    {
        $parsed = ['sm' => '1:1', 'md' => '16:9'];
        $result = $this->transformer->cascadeRatios($parsed);
        $this->assertEquals([
            'sm' => '1:1',
            'md' => '16:9',
            'lg' => '16:9',
            'xl' => '16:9',
            '2xl' => '16:9',
        ], $result);
    }
}
