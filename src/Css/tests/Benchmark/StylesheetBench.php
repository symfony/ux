<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Benchmark;

use PhpBench\Attributes as Bench;
use Symfony\UX\Css\Dumper\StylesheetDumper;

#[Bench\AfterMethods('tearDown')]
#[Bench\OutputTimeUnit('milliseconds', precision: 2)]
final class StylesheetBench
{
    private const TEMPLATES = 200;

    private BenchProject $project;
    private StylesheetDumper $dumper;

    public function setUpDebug(): void
    {
        $this->boot(true);
    }

    public function setUpDebugWithAChangedTemplate(): void
    {
        $this->boot(true);
        $this->project->changeTemplate(0);
    }

    public function setUpProduction(): void
    {
        $this->boot(false);
    }

    public function tearDown(): void
    {
        $this->project->remove();
    }

    #[Bench\BeforeMethods('setUpDebug')]
    #[Bench\Revs(50)]
    #[Bench\Iterations(5)]
    #[Bench\Warmup(1)]
    #[Bench\Assert('mode(variant.time.avg) < 10 milliseconds')]
    public function benchDevRequestWithoutChange(): void
    {
        $this->dumper->update();
    }

    #[Bench\BeforeMethods('setUpDebugWithAChangedTemplate')]
    #[Bench\Revs(1)]
    #[Bench\Iterations(10)]
    #[Bench\Assert('mode(variant.time.avg) < 200 milliseconds')]
    public function benchDevRequestAfterATemplateChanged(): void
    {
        $this->dumper->update();
    }

    #[Bench\BeforeMethods('setUpProduction')]
    #[Bench\Revs(1)]
    #[Bench\Iterations(5)]
    #[Bench\Assert('mode(variant.time.avg) < 2500 milliseconds')]
    public function benchCacheWarmup(): void
    {
        $this->dumper->dump();
    }

    private function boot(bool $debug): void
    {
        $this->project = new BenchProject(self::TEMPLATES, $debug);
        $dumper = $this->project->get('ux_css.stylesheet_dumper');
        \assert($dumper instanceof StylesheetDumper);
        $this->dumper = $dumper;
    }
}
