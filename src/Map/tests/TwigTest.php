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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Renderer\RendererInterface;
use Symfony\UX\Map\Tests\Kernel\TwigAppKernel;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;

final class TwigTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TwigAppKernel::class;
    }

    public function testRenderMap(): void
    {
        $map = new Map();
        $attributes = ['data-foo' => 'bar'];

        $renderer = self::createMock(RendererInterface::class);
        $renderer
            ->expects(self::once())
            ->method('renderMap')
            ->with($map, $attributes)
            ->willReturn('<div data-controller="@symfony/ux-foobar-map"></div>')
        ;

        self::getContainer()->set('test.ux_map.renderers', $renderer);

        /** @var \Twig\Environment $twig */
        $twig = self::getContainer()->get('twig');
        $twig->setLoader(new ChainLoader([
            new ArrayLoader([
                'test' => '{{ render_map(map, attributes) }}',
            ]),
            $twig->getLoader(),
        ]));

        self::assertSame(
            '<div data-controller="@symfony/ux-foobar-map"></div>',
            $twig->render('test', ['map' => $map, 'attributes' => $attributes])
        );
    }
}
