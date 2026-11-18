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

use App\Bench\MarkdownTables;
use App\Bench\Measurement;
use App\Bench\PageStats;
use App\Bench\ResultsWriter;
use App\Bench\Round;
use App\Bench\RunInfo;
use App\Bench\Target;
use App\Bench\TargetProvider;
use App\Bench\TargetStats;
use PHPUnit\Framework\TestCase;

final class ResultsWriterTest extends TestCase
{
    public function testItWritesOneSectionPerPackageWithMediansAndRanges(): void
    {
        $run = $this->runInfo(false);
        $stats = PageStats::fromHtml('<div class="a b"></div>');
        $targets = array_map(
            static fn (Target $target): TargetStats => new TargetStats($target, $stats),
            new TargetProvider()->only(['css']),
        );
        $rounds = [];
        foreach (['classic', 'worker'] as $mode) {
            foreach ([1, 2, 3] as $index => $round) {
                $rounds[] = new Round($mode, 'css/tailwind', $round, new Measurement([100.0, 110.0, 120.0][$index], 8.0, 9.0, 10.0, 0));
                $rounds[] = new Round($mode, 'css/ux-css', $round, new Measurement([200.0, 190.0, 210.0][$index], 8.0, 9.0, 10.0, 0));
            }
        }

        $output = new ResultsWriter(new TargetProvider())->write($run, $targets, $rounds);

        self::assertStringContainsString('# Results', $output);
        self::assertStringContainsString('## Css', $output);
        self::assertStringContainsString('from symfony/ux abc1234.', $output);
        self::assertMatchesRegularExpression('/\| classic\s+\| Tailwind CSS \(`\/css\/tailwind`\)\s+\| 110\.0\s+\| 100\.0-120\.0\s+\|/', $output);
        self::assertStringContainsString('- classic mode: UX CSS serves +81.8% requests per second compared to Tailwind CSS', $output);
        self::assertSame($output, MarkdownTables::align($output));
    }

    public function testItMentionsUncommittedChangesAndWhatAPageHolds(): void
    {
        $run = $this->runInfo(true);
        $target = new TargetProvider()->only(['twig-component'])[0];
        $stats = PageStats::fromHtml('<div data-slot="x"></div><div data-slot="y"></div>');
        $rounds = [new Round('classic', 'twig-component/dashboard', 1, new Measurement(50.0, 20.0, 30.0, 40.0, 2))];

        $output = new ResultsWriter(new TargetProvider())->write($run, [new TargetStats($target, $stats)], $rounds);

        self::assertStringContainsString('from symfony/ux abc1234 with uncommitted changes.', $output);
        self::assertStringContainsString('## TwigComponent', $output);
        self::assertStringContainsString('2 components', $output);
        self::assertStringNotContainsString('## Css', $output);
    }

    public function testItWritesALivePackageWithoutPageStats(): void
    {
        $run = $this->runInfo(false);
        $rounds = [new Round('classic', 'live/table-search', 1, new Measurement(80.0, 10.0, 20.0, 30.0, 0))];

        $output = new ResultsWriter(new TargetProvider())->write($run, [], $rounds);

        self::assertStringContainsString('## LiveComponent', $output);
        self::assertMatchesRegularExpression('/\| classic\s+\| `live\/table-search`\s+\| 80\.0\s+\|/', $output);
        self::assertStringNotContainsString('Holds', $output);
    }

    private function runInfo(bool $dirty): RunInfo
    {
        return new RunInfo(
            'bin/bench 3 1000 4',
            '2026-11-18 10:00 UTC',
            'abc1234',
            $dirty,
            'FrankenPHP v1.13.5 with PHP 8.5.12',
            '8.2.0-BETA1',
            '3.30.0',
            '1.5.0',
            'Apple M1 Pro',
            10,
            'Darwin 27.2.0',
            4,
            500,
            3,
            1000,
            1.5,
            2.25,
        );
    }
}
