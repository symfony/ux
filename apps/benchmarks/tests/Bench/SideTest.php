<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Bench;

use App\Bench\Side;
use App\Bench\VendorVersions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SideTest extends TestCase
{
    public function testBaseUrlUsesThePortOnTheLoopback(): void
    {
        $side = $this->side('3.x', 8181, true);

        $baseUrl = $side->baseUrl();

        self::assertSame('http://127.0.0.1:8181', $baseUrl);
    }

    public function testWorkingTreeLogFileHasNoSlug(): void
    {
        $side = $this->side('3.x (abc1234)', 8180, true);

        $logFile = $side->logFile('classic');

        self::assertSame('frankenphp-classic.log', $logFile);
    }

    #[DataProvider('provideOtherSideLogFiles')]
    public function testOtherSideLogFileCarriesTheSlug(string $label, string $expected): void
    {
        $side = $this->side($label, 8181, false);

        $logFile = $side->logFile('worker');

        self::assertSame($expected, $logFile);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOtherSideLogFiles(): iterable
    {
        yield 'branch with a slash' => ['origin/3.x', 'frankenphp-worker-origin-3.x.log'];
        yield 'tag' => ['v3.6.0', 'frankenphp-worker-v3.6.0.log'];
    }

    #[DataProvider('provideSlugs')]
    public function testSlug(string $label, string $expected): void
    {
        $slug = Side::slug($label);

        self::assertSame($expected, $slug);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideSlugs(): iterable
    {
        yield 'slash' => ['origin/3.x', 'origin-3.x'];
        yield 'tag is kept' => ['v3.6.0', 'v3.6.0'];
        yield 'spaces and parentheses collapse' => ['3.x (abc1234)', '3.x-abc1234-'];
        yield 'consecutive specials collapse' => ['a//b', 'a-b'];
    }

    private function side(string $label, int $port, bool $isWorkingTree): Side
    {
        return new Side($label, '/tmp', '/tmp', $port, 'abc1234', false, VendorVersions::of('8.2.0', '3.30.0', '1.5.0'), $isWorkingTree);
    }
}
