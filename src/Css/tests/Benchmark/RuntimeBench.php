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
use Symfony\UX\Css\Twig\CssRuntime;
use Twig\Environment;

#[Bench\BeforeMethods('setUp')]
#[Bench\AfterMethods('tearDown')]
#[Bench\Revs(2000)]
#[Bench\Iterations(5)]
#[Bench\Warmup(1)]
#[Bench\OutputTimeUnit('microseconds', precision: 2)]
final class RuntimeBench
{
    private const FLAT = [
        'display' => 'flex',
        'alignItems' => 'center',
        'gap' => '2',
        'p' => '4',
        'color' => 'gray.900',
        'bg' => 'gray.100',
        'rounded' => 'md',
    ];

    private const CONDITIONS = [
        'color' => 'gray.900',
        '_hover' => ['color' => 'blue.500', 'bg' => 'gray.100'],
        '_dark' => ['color' => 'gray.100', '_hover' => ['bg' => 'gray.900']],
        '_focusVisible' => ['outlineWidth' => '[2px]'],
    ];

    private const RESPONSIVE = [
        'p' => ['2', '4', '8'],
        'fontSize' => ['base' => 'sm', 'md' => 'lg'],
        'display' => ['base' => 'block', 'lg' => 'flex'],
        'gap' => ['base' => '1', 'sm' => '2', 'xl' => '4'],
    ];

    private BenchProject $project;
    private CssRuntime $runtime;

    public function setUp(): void
    {
        $this->project = new BenchProject();
        $twig = $this->project->get('twig');
        \assert($twig instanceof Environment);
        $this->runtime = $twig->getRuntime(CssRuntime::class);
    }

    public function tearDown(): void
    {
        $this->project->remove();
    }

    #[Bench\Assert('mode(variant.time.avg) < 50 microseconds')]
    public function benchFlatHash(): void
    {
        $this->runtime->css(self::FLAT);
    }

    #[Bench\Assert('mode(variant.time.avg) < 75 microseconds')]
    public function benchConditions(): void
    {
        $this->runtime->css(self::CONDITIONS);
    }

    #[Bench\Assert('mode(variant.time.avg) < 100 microseconds')]
    public function benchResponsive(): void
    {
        $this->runtime->css(self::RESPONSIVE);
    }

    #[Bench\Assert('mode(variant.time.avg) < 15 microseconds')]
    public function benchDynamicPartOfAHash(): void
    {
        $this->runtime->append('d_flex ai_center p_4', ['color' => 'blue.500']);
    }
}
