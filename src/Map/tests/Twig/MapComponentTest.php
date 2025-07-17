<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Tests\Twig;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Renderer\RendererInterface;
use Symfony\UX\Map\Tests\Kernel\TwigComponentKernel;

class MapComponentTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TwigComponentKernel::class;
    }

    public function testRenderMapComponent(): void
    {
        $map = (new Map())
            ->center(new Point(latitude: 5, longitude: 10))
            ->zoom(4);
        $attributes = ['data-foo' => 'bar'];

        $renderer = self::createMock(RendererInterface::class);
        $renderer
            ->method('renderMap')
            ->with($map, $attributes)
            ->willReturn('<div data-controller="@symfony/ux-foobar-map"></div>')
        ;
        self::getContainer()->set('test.ux_map.renderers', $renderer);

        $twig = self::getContainer()->get('twig');
        $template = $twig->createTemplate('<twig:ux:map center="{{ {lat: 5, lng: 10} }}" zoom="4" data-foo="bar" />');

        $this->assertSame(
            '<div data-controller="@symfony/ux-foobar-map"></div>',
            $template->render(['attributes' => $attributes]),
        );
    }
}
