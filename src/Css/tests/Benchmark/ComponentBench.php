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
final class ComponentBench
{
    private const BUTTON = <<<'TWIG'
        <button {{ attributes.defaults({ class: css({ display: 'inline-flex', alignItems: 'center', gap: '2', px: '4', py: '2', color: 'gray.900' }) }) }}>{{ label|default('Go') }}</button>
        TWIG;

    private const BUTTONS = 20;

    private BenchProject $project;
    private TemplateWrapper $page;

    public function setUp(): void
    {
        $this->project = new BenchProject(0, false, true);
        $this->project->writeTemplate('components/Button.html.twig', self::BUTTON);
        $this->project->writeTemplate('page.html.twig', str_repeat("<twig:Button class=\"{{ css({ px: '8' }) }}\" />\n", self::BUTTONS));

        $twig = $this->project->get('twig');
        \assert($twig instanceof Environment);
        $this->page = $twig->load('page.html.twig');
    }

    public function tearDown(): void
    {
        $this->project->remove();
    }

    #[Bench\Assert('mode(variant.time.avg) < 1000 microseconds')]
    public function benchPageOfComponentsWithCallerClasses(): void
    {
        $this->page->render([]);
    }
}
