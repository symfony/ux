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
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Resolver\TokenTreeBuilder;
use Symfony\UX\DesignTokens\Token\ColorToken;
use Symfony\UX\DesignTokens\Token\DimensionToken;

#[CoversClass(TokenTreeBuilder::class)]
final class DtcgTokenTreeBuilderTest extends TestCase
{
    public function testResolvesStructuredValuesAndRootTokens(): void
    {
        $result = new TokenTreeBuilder()->resolve([
            'space' => [
                '$type' => 'dimension',
                '$root' => ['$value' => ['value' => 16, 'unit' => 'px']],
            ],
            'brand' => [
                'primary' => ['$type' => 'color', '$value' => [
                    'colorSpace' => 'srgb',
                    'components' => [0.2, 0.4, 0.8],
                ]],
            ],
        ]);

        self::assertInstanceOf(DimensionToken::class, $result['space']['$root']);
        self::assertSame('16px', (string) $result['space']['$root']);
        self::assertInstanceOf(ColorToken::class, $result['brand']['primary']);
    }

    public function testAppliesGroupExtends(): void
    {
        $result = new TokenTreeBuilder()->resolve([
            'base' => [
                '$type' => 'dimension',
                'small' => ['$value' => ['value' => 8, 'unit' => 'px']],
            ],
            'derived' => [
                '$extends' => '{base}',
                'large' => ['$value' => ['value' => 2, 'unit' => 'rem']],
            ],
        ]);

        self::assertSame('8px', (string) $result['derived']['small']);
        self::assertSame('2rem', (string) $result['derived']['large']);
    }
}
