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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Css\Tests\Fixtures\Dtcg;
use Symfony\UX\Css\Tests\Fixtures\TestKernel;

final class BenchProject
{
    private readonly string $dir;
    private readonly TestKernel $kernel;

    public function __construct(int $templates = 0, bool $debug = false)
    {
        $this->dir = sys_get_temp_dir().'/ux_css_bench_'.uniqid();
        $filesystem = new Filesystem();
        $filesystem->mkdir($this->dir.'/templates');

        for ($i = 0; $i < $templates; ++$i) {
            $path = $this->templatePath($i);
            $filesystem->dumpFile($path, self::template($i));
            // older than the index the kernel writes when it boots, so that the template counts as unchanged
            touch($path, time() - 60);
        }

        $this->kernel = new TestKernel(
            debug: $debug,
            projectDir: $this->dir,
            designTokens: self::designTokens(),
        );
        $this->kernel->boot();
    }

    public function get(string $id): object
    {
        return $this->kernel->getContainer()->get($id);
    }

    public function containerClass(): string
    {
        return $this->kernel->getContainer()::class;
    }

    public function writeTemplate(string $name, string $code): void
    {
        new Filesystem()->dumpFile($this->dir.'/templates/'.$name, $code);
    }

    public function changeTemplate(int $i): void
    {
        touch($this->templatePath($i), time() + 1);
    }

    public function remove(): void
    {
        new Filesystem()->remove([$this->dir, $this->kernel->getBuildDir()]);
    }

    public static function template(int $i): string
    {
        $width = $i % 50 + 10;

        return <<<TWIG
            <section class="{{ css({ display: 'flex', flexDirection: { base: 'column', md: 'row' }, gap: '4', p: '8' }) }}">
                <h2 class="{{ css({ fontSize: { base: 'sm', lg: 'lg' }, color: 'gray.900' }) }}">Section {$i}</h2>
                <p class="{{ css({ mt: '2', color: 'gray.900', maxW: '[{$width}rem]' }) }}">{{ text|default('') }}</p>
                <a href="#" class="{{ css({ color: 'blue.500', _hover: { bg: 'gray.100' }, rounded: 'md', px: '2' }) }}">
                    Read more
                </a>
                <button class="{{ css({ p: '1', bg: tone|default('gray.100') }) }}">Toggle</button>
            </section>
            TWIG;
    }

    private function templatePath(int $i): string
    {
        return \sprintf('%s/templates/section_%d.html.twig', $this->dir, $i);
    }

    private static function designTokens(): array
    {
        return [
            'color' => [
                'gray' => ['100' => Dtcg::color('#f3f4f6'), '900' => Dtcg::color('#111827')],
                'blue' => ['500' => Dtcg::color('#3b82f6')],
            ],
            'dimension' => [
                'spacing' => [
                    '1' => Dtcg::dimension(0.25),
                    '2' => Dtcg::dimension(0.5),
                    '4' => Dtcg::dimension(1),
                    '8' => Dtcg::dimension(2),
                ],
                'radius' => ['md' => Dtcg::dimension(0.375)],
            ],
            'font' => ['size' => ['sm' => Dtcg::dimension(0.875), 'lg' => Dtcg::dimension(1.125)]],
        ];
    }
}
