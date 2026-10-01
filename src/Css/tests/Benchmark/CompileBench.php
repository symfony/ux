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
use Twig\Environment;

#[Bench\BeforeMethods('setUp')]
#[Bench\AfterMethods('tearDown')]
#[Bench\Revs(20)]
#[Bench\Iterations(5)]
#[Bench\Warmup(1)]
#[Bench\OutputTimeUnit('milliseconds', precision: 3)]
final class CompileBench
{
    private const CSS_CALL = <<<'TWIG'
        <div class="{{ css({ display: 'flex', gap: '4', p: { base: '2', md: '8' }, _hover: { color: 'blue.500' } }) }}"></div>
        TWIG;

    private const CALLS = 100;

    private const PLAIN_CALL = <<<'TWIG'
        <div class="{{ classes|default('d_flex gap_4 p_2 md:p_8 hover:c_blue.500') }}"></div>
        TWIG;

    private BenchProject $project;
    private Environment $twig;

    public function setUp(): void
    {
        $this->project = new BenchProject();
        $twig = $this->project->get('twig');
        \assert($twig instanceof Environment);
        $this->twig = $twig;

        $this->project->writeTemplate('css.html.twig', str_repeat(self::CSS_CALL."\n", self::CALLS));
        $this->project->writeTemplate('plain.html.twig', str_repeat(self::PLAIN_CALL."\n", self::CALLS));
    }

    public function tearDown(): void
    {
        $this->project->remove();
    }

    #[Bench\Assert('mode(variant.time.avg) < 250 milliseconds')]
    public function benchTemplateWithCssCalls(): void
    {
        $this->compile('css.html.twig');
    }

    #[Bench\Assert('mode(variant.time.avg) < 150 milliseconds')]
    public function benchTemplateWithoutCssCalls(): void
    {
        $this->compile('plain.html.twig');
    }

    private function compile(string $name): void
    {
        $source = $this->twig->getLoader()->getSourceContext($name);
        $this->twig->compileSource($source);
    }
}
