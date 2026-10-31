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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Generator\ColorScheme;

#[CoversClass(ColorScheme::class)]
final class ColorSchemeTest extends TestCase
{
    /**
     * @param array<string, array{contexts: list<string>, default: string|null}> $modifiers
     * @param array<string, string>                                              $inputs
     * @param array{array<string, string>, array<string, string>}|null           $expected
     */
    #[DataProvider('pairs')]
    public function testPairsTheLightAndDarkContexts(array $modifiers, array $inputs, ?array $expected): void
    {
        self::assertSame($expected, new ColorScheme()->contexts($modifiers, $inputs));
    }

    /** @return iterable<string, array{array<string, array{contexts: list<string>, default: string|null}>, array<string, string>, array{array<string, string>, array<string, string>}|null}> */
    public static function pairs(): iterable
    {
        $scheme = ['scheme' => ['contexts' => ['light', 'dark'], 'default' => 'light']];

        yield 'both contexts declared' => [$scheme, [], [['scheme' => 'light'], ['scheme' => 'dark']]];
        yield 'other inputs kept' => [$scheme + ['brand' => ['contexts' => ['a', 'b'], 'default' => 'a']], ['brand' => 'b'], [['brand' => 'b', 'scheme' => 'light'], ['brand' => 'b', 'scheme' => 'dark']]];
        yield 'names compared without case' => [['Scheme' => ['contexts' => ['Light', 'DARK'], 'default' => null]], [], [['scheme' => 'light'], ['scheme' => 'dark']]];
        yield 'no Resolver document' => [[], [], null];
        yield 'no such modifier' => [['brand' => ['contexts' => ['a', 'b'], 'default' => 'a']], [], null];
        yield 'a context missing' => [['scheme' => ['contexts' => ['light', 'dim'], 'default' => 'light']], [], null];
        yield 'the scheme already selected' => [$scheme, ['Scheme' => 'dark'], null];
    }

    public function testUsesTheConfiguredNames(): void
    {
        self::assertSame(
            [['mode' => 'day'], ['mode' => 'night']],
            new ColorScheme('mode', 'day', 'night')->contexts(['mode' => ['contexts' => ['day', 'night'], 'default' => 'day']], []),
        );
    }
}
