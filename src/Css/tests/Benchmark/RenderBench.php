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
use Twig\TemplateWrapper;

#[Bench\BeforeMethods('setUp')]
#[Bench\AfterMethods('tearDown')]
#[Bench\Revs(500)]
#[Bench\Iterations(5)]
#[Bench\Warmup(1)]
#[Bench\OutputTimeUnit('microseconds', precision: 2)]
final class RenderBench
{
    private const SECTIONS = 20;

    private BenchProject $project;
    private TemplateWrapper $page;

    public function setUp(): void
    {
        $this->project = new BenchProject(self::SECTIONS);
        $includes = '';
        for ($i = 0; $i < self::SECTIONS; ++$i) {
            $includes .= \sprintf("{{ include('section_%d.html.twig') }}\n", $i);
        }
        $this->project->writeTemplate('page.html.twig', $includes);

        $twig = $this->project->get('twig');
        \assert($twig instanceof Environment);
        $this->page = $twig->load('page.html.twig');
    }

    public function tearDown(): void
    {
        $this->project->remove();
    }

    #[Bench\Assert('mode(variant.time.avg) < 500 microseconds')]
    public function benchPageOfSections(): void
    {
        $this->page->render(['text' => 'Some text', 'tone' => 'blue.500']);
    }
}
