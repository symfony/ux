<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Map\Renderer\NullRenderer;
use Symfony\UX\Map\Renderer\RendererInterface;
use Symfony\UX\Map\Tests\Kernel\FrameworkAppKernel;
use Symfony\UX\Map\Tests\Kernel\TwigAppKernel;

class UXMapBundleTest extends TestCase
{
    /**
     * @return iterable<array{0: class-string<Kernel>}>
     */
    public static function provideKernelClasses(): iterable
    {
        yield 'framework' => [FrameworkAppKernel::class];
        yield 'twig' => [TwigAppKernel::class];
    }

    /**
     * @dataProvider provideKernelClasses
     *
     * @param class-string<Kernel> $kernelClass
     */
    public function testBootKernel(string $kernelClass): void
    {
        $kernel = new $kernelClass('test', true);
        $kernel->boot();

        self::assertArrayHasKey('UXMapBundle', $kernel->getBundles());
    }

    /**
     * @dataProvider provideKernelClasses
     *
     * @param class-string<Kernel> $kernelClass
     */
    public function testNullRendererAsDefault(string $kernelClass): void
    {
        $expectedRenderer = new NullRenderer(['symfony/ux-google-map', 'symfony/ux-leaflet-map']);

        $kernel = new $kernelClass('test', true);
        $kernel->boot();
        $container = $kernel->getContainer();

        $defaultRenderer = $this->extractDefaultRenderer($container);
        self::assertEquals($expectedRenderer, $defaultRenderer, 'The default renderer should be a NullRenderer.');

        $renderers = $this->extractRenderers($container);
        self::assertEquals(['default' => $expectedRenderer], $renderers, 'The renderers should only contain the main renderer, which is a NullRenderer.');
    }

    private function extractDefaultRenderer(ContainerInterface $container): RendererInterface
    {
        $renderers = $container->get('test.ux_map.renderers');

        return \Closure::bind(fn () => $this->default, $renderers, $renderers::class)();
    }

    /**
     * @return array<string, RendererInterface>
     */
    private function extractRenderers(ContainerInterface $container): array
    {
        $renderers = $container->get('test.ux_map.renderers');

        return \Closure::bind(fn () => $this->renderers, $renderers, $renderers::class)();
    }
}
