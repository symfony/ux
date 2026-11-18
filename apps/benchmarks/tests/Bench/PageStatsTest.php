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

use App\Bench\PageStats;
use PHPUnit\Framework\TestCase;

final class PageStatsTest extends TestCase
{
    public function testItCountsElementsAndClasses(): void
    {
        $html = file_get_contents(__DIR__.'/../Fixtures/dashboard.html');

        $stats = PageStats::fromHtml($html);

        self::assertSame(['components' => 3, 'rows' => 2, 'icons' => 2, 'markers' => 0, 'live_children' => 0], $stats->counts());
        self::assertSame(2, $stats->classAttributes());
        self::assertSame(4, $stats->classes());
        self::assertSame(3, $stats->uniqueClasses());
        self::assertSame(\strlen($html), $stats->bytes());
    }

    public function testItDescribesWhatItHoldsWithSingularsAndPlurals(): void
    {
        $html = '<table><tbody><tr></tr></tbody></table><svg></svg><svg></svg><div data-live-fingerprint-value="a"></div>';
        $stats = PageStats::fromHtml($html);

        $holds = $stats->holds();

        self::assertSame('1 row, 2 icons, 1 live child', $holds);
    }

    public function testItCountsMapMarkers(): void
    {
        $html = file_get_contents(__DIR__.'/../Fixtures/map.html');

        $stats = PageStats::fromHtml($html);

        self::assertSame(2, $stats->counts()['markers']);
    }
}
