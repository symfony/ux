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
use Symfony\UX\Css\Tests\Fixtures\TestKernel;

final class BenchProject
{
    public const CONFIG = [
        'tokens' => [
            'colors' => [
                'gray' => ['100' => '#f3f4f6', '900' => '#111827'],
                'blue' => ['500' => '#3b82f6'],
            ],
            'spacing' => ['1' => '0.25rem', '2' => '0.5rem', '4' => '1rem', '8' => '2rem'],
            'fontSizes' => ['sm' => '0.875rem', 'lg' => '1.125rem'],
            'radii' => ['md' => '0.375rem'],
        ],
    ];

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

        $this->kernel = new TestKernel(self::CONFIG, 'test', $debug, $this->dir);
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
}
