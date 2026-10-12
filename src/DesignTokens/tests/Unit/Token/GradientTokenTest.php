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
use Symfony\UX\DesignTokens\Token\GradientToken;

#[CoversClass(GradientToken::class)]
final class GradientTokenTest extends TestCase
{
    public function testTypeAndCssValue(): void
    {
        $token = new GradientToken([self::stop(0, 0), self::stop(1, 1)]);

        self::assertSame('gradient', $token->getType());
        self::assertSame('linear-gradient(color(srgb 0 0 0) 0%, color(srgb 1 1 1) 100%)', (string) $token);
    }

    public function testClampsPositionsToTheDtcgRange(): void
    {
        $token = new GradientToken([self::stop(0, -99), self::stop(1, 42)]);

        self::assertSame(0, $token->getValue()[0]['position']);
        self::assertSame(1, $token->getValue()[1]['position']);
    }

    public function testStopPositionsBecomePercentages(): void
    {
        $token = new GradientToken([self::stop(0, 0), self::stop(0.5, 0.355), self::stop(1, 1)]);

        self::assertSame('linear-gradient(color(srgb 0 0 0) 0%, color(srgb 0.5 0.5 0.5) 35.5%, color(srgb 1 1 1) 100%)', (string) $token);
    }

    /** @return array<string, mixed> */
    private static function stop(int|float $grey, int|float $position): array
    {
        return ['color' => ['colorSpace' => 'srgb', 'components' => [$grey, $grey, $grey]], 'position' => $position];
    }
}
