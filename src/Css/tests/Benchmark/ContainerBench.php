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

#[Bench\BeforeMethods('setUp')]
#[Bench\AfterMethods('tearDown')]
#[Bench\Revs(500)]
#[Bench\Iterations(5)]
#[Bench\Warmup(1)]
#[Bench\OutputTimeUnit('microseconds', precision: 2)]
final class ContainerBench
{
    private BenchProject $project;
    private string $containerClass;

    public function setUp(): void
    {
        $this->project = new BenchProject();
        $this->containerClass = $this->project->containerClass();
    }

    public function tearDown(): void
    {
        $this->project->remove();
    }

    #[Bench\Assert('mode(variant.time.avg) < 30 microseconds')]
    public function benchLoadTheClassNameGenerator(): void
    {
        $container = new $this->containerClass();
        $container->get('ux_css.class_name_generator');
    }
}
