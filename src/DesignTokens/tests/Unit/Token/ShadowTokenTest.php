<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Token;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Token\ShadowToken;

#[CoversClass(ShadowToken::class)]
final class ShadowTokenTest extends TestCase
{
    public function testTypeIsShadow(): void
    {
        self::assertSame('shadow', new ShadowToken(self::shadow(0, 4, 8))->getType());
    }

    public function testToStringForOneLayer(): void
    {
        self::assertSame('0px 4px 8px 0px color(srgb 0 0 0 / 0.2)', (string) new ShadowToken(self::shadow(0, 4, 8)));
    }

    public function testToStringForSeveralLayers(): void
    {
        $token = new ShadowToken([self::shadow(0, 2, 4), self::shadow(0, 8, 16), self::shadow(0, 12, 24)]);

        self::assertSame('0px 2px 4px 0px color(srgb 0 0 0 / 0.2), 0px 8px 16px 0px color(srgb 0 0 0 / 0.2), 0px 12px 24px 0px color(srgb 0 0 0 / 0.2)', (string) $token);
    }

    public function testAnInsetLayerStartsWithTheKeyword(): void
    {
        self::assertStringStartsWith('inset ', (string) new ShadowToken([...self::shadow(0, 1, 2), 'inset' => true]));
    }

    /** @return array<string, mixed> */
    private static function shadow(int $x, int $y, int $blur): array
    {
        return [
            'color' => ['colorSpace' => 'srgb', 'components' => [0, 0, 0], 'alpha' => 0.2],
            'offsetX' => ['value' => $x, 'unit' => 'px'],
            'offsetY' => ['value' => $y, 'unit' => 'px'],
            'blur' => ['value' => $blur, 'unit' => 'px'],
            'spread' => ['value' => 0, 'unit' => 'px'],
        ];
    }
}
