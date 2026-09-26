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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Token\TransitionToken;

#[CoversClass(TransitionToken::class)]
final class TransitionTokenTest extends TestCase
{
    public function testTypeIsTransition(): void
    {
        self::assertSame('transition', new TransitionToken(self::transition(150, [0, 0, 1, 1], 0))->getType());
    }

    /** @param list<int|float> $curve */
    #[DataProvider('transitions')]
    public function testToStringFormatsTheShorthand(int $duration, array $curve, int $delay, string $expected): void
    {
        self::assertSame($expected, (string) new TransitionToken(self::transition($duration, $curve, $delay)));
    }

    /** @return iterable<string, array{int, list<int|float>, int, string}> */
    public static function transitions(): iterable
    {
        yield 'fast' => [150, [0.2, 0, 0, 1], 0, '150ms cubic-bezier(0.2, 0, 0, 1) 0ms'];
        yield 'spring with delay' => [500, [0.5, -0.5, 0.5, 1.5], 100, '500ms cubic-bezier(0.5, -0.5, 0.5, 1.5) 100ms'];
    }

    /**
     * @param list<int|float> $curve
     *
     * @return array<string, mixed>
     */
    private static function transition(int $duration, array $curve, int $delay): array
    {
        return ['duration' => ['value' => $duration, 'unit' => 'ms'], 'timingFunction' => $curve, 'delay' => ['value' => $delay, 'unit' => 'ms']];
    }
}
