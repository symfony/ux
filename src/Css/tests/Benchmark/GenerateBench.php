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
use Symfony\UX\Css\CssGenerator;

#[Bench\BeforeMethods('setUp')]
#[Bench\AfterMethods('tearDown')]
#[Bench\Revs(5)]
#[Bench\Iterations(5)]
#[Bench\Warmup(1)]
#[Bench\OutputTimeUnit('milliseconds', precision: 2)]
final class GenerateBench
{
    private const HASHES = 1000;
    private const SPACING = ['1', '2', '4', '8'];
    private const COLORS = ['gray.100', 'gray.900', 'blue.500'];

    private BenchProject $project;
    private CssGenerator $generator;

    /**
     * @var list<array<string, mixed>>
     */
    private array $hashes;

    public function setUp(): void
    {
        $this->project = new BenchProject();
        $generator = $this->project->get('ux_css.css_generator');
        \assert($generator instanceof CssGenerator);
        $this->generator = $generator;
        $this->hashes = self::hashes(self::HASHES);
    }

    public function tearDown(): void
    {
        $this->project->remove();
    }

    #[Bench\Assert('mode(variant.time.avg) < 750 milliseconds')]
    public function benchCompactStylesheet(): void
    {
        $this->generator->generate($this->hashes, true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function hashes(int $count): array
    {
        $hashes = [];
        for ($i = 0; $i < $count; ++$i) {
            $spacing = self::SPACING[$i % 4];
            $color = self::COLORS[$i % 3];

            $hashes[] = [
                'display' => 'flex',
                'p' => ['base' => $spacing, 'md' => self::SPACING[($i + 1) % 4]],
                'color' => $color,
                '_hover' => ['bg' => self::COLORS[($i + 1) % 3]],
                'maxW' => \sprintf('[%drem]', $i % 50 + 10),
                'w' => \sprintf('[%dpx]', $i),
            ];
        }

        return $hashes;
    }
}
