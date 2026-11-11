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
use Symfony\UX\Css\Cva;
use Symfony\UX\Css\Engine\ClassNameGenerator;
use Symfony\UX\Css\Engine\CvaRecipe;
use Symfony\UX\Css\Twig\CssRuntime;
use Twig\Environment;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
#[Bench\BeforeMethods('setUp')]
#[Bench\AfterMethods('tearDown')]
#[Bench\Revs(2000)]
#[Bench\Iterations(5)]
#[Bench\Warmup(1)]
#[Bench\OutputTimeUnit('microseconds', precision: 2)]
final class CvaBench
{
    private const BUTTON = [
        'base' => ['display' => 'flex', 'alignItems' => 'center', 'rounded' => 'md'],
        'variants' => [
            'tone' => [
                'light' => ['bg' => 'gray.100', 'color' => 'gray.900'],
                'dark' => ['bg' => 'gray.900', 'color' => 'gray.100'],
            ],
            'size' => [
                'sm' => ['p' => '2', 'fontSize' => 'sm'],
                'lg' => ['p' => '4', 'fontSize' => 'lg'],
            ],
        ],
        'defaultVariants' => ['tone' => 'light', 'size' => 'sm'],
        'compoundVariants' => [
            ['tone' => 'dark', 'size' => 'lg', 'css' => ['gap' => '8']],
        ],
    ];

    private const PROPS = ['tone' => 'dark', 'size' => 'lg'];

    private BenchProject $project;
    private CssRuntime $runtime;
    private Cva $fromTheTable;
    private Cva $withoutTable;

    public function setUp(): void
    {
        $this->project = new BenchProject();
        $twig = $this->project->get('twig');
        \assert($twig instanceof Environment);
        $this->runtime = $twig->getRuntime(CssRuntime::class);

        $generator = $this->project->get('ux_css.class_name_generator');
        \assert($generator instanceof ClassNameGenerator);
        $recipe = CvaRecipe::fromConfig(self::BUTTON);
        $combinations = $recipe->combinations(256);
        \assert(null !== $combinations);

        $table = [];
        foreach ($combinations as $props) {
            $table[$recipe->key($props)] = $generator->classes($recipe->resolve($props, $generator));
        }
        $this->fromTheTable = new Cva($recipe, $table, $this->runtime, false);
        $this->withoutTable = $this->runtime->compiledCva(self::BUTTON, $recipe->id(), []);
    }

    public function tearDown(): void
    {
        $this->project->remove();
    }

    #[Bench\Assert('mode(variant.time.avg) < 15 microseconds')]
    public function benchApplyFromTheTable(): void
    {
        $this->fromTheTable->apply(self::PROPS);
    }

    #[Bench\Assert('mode(variant.time.avg) < 20 microseconds')]
    public function benchApplyAtRuntime(): void
    {
        $this->withoutTable->apply(self::PROPS);
    }
}
