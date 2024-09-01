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

use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Renderer\RendererInterface;
use Symfony\UX\Map\Tests\Kernel\TwigAppKernel;
use Symfony\UX\Map\Twig\MapExtension;
use Symfony\UX\Map\Twig\MapRuntime;
use Twig\DeprecatedCallableInfo;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;

class MapExtensionTest extends KernelTestCase
{
    use ExpectDeprecationTrait;

    protected static function getKernelClass(): string
    {
        return TwigAppKernel::class;
    }

    public function testExtensionIsRegistered(): void
    {
        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');

        $this->assertTrue($twig->hasExtension(MapExtension::class));
        $this->assertInstanceOf(MapExtension::class, $twig->getExtension(MapExtension::class));
    }

    public function testRuntimeIsRegistered(): void
    {
        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');

        $this->assertInstanceOf(MapRuntime::class, $twig->getRuntime(MapRuntime::class));
    }

    /**
     * @group legacy
     */
    public function testRenderFunctionIsDeprecated(): void
    {
        $map = (new Map())
           ->center(new Point(latitude: 5, longitude: 10))
           ->zoom(4);

        $renderer = self::createMock(RendererInterface::class);
        $renderer
            ->expects(self::once())
            ->method('renderMap')
            ->with($map, [])
            ->willReturn('<map/>')
        ;
        self::getContainer()->set('test.ux_map.renderers', $renderer);

        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');
        $twig->setLoader(new ChainLoader([
            new ArrayLoader([
                'test' => '{{ render_map(map) }}',
            ]),
            $twig->getLoader(),
        ]));

        if (class_exists(DeprecatedCallableInfo::class)) {
            $this->expectDeprecation('Since symfony/ux-map 2.20: Twig Function "render_map" is deprecated; use "ux_map" instead in test at line 1.');
        } else {
            $this->expectDeprecation('Since symfony/ux-map 2.20: Twig Function "render_map" is deprecated. Use "ux_map" instead in test at line 1.');
        }
        $html = $twig->render('test', ['map' => $map]);
        $this->assertSame('<map/>', $html);
    }

    public function testMapFunctionWithArray(): void
    {
        $map = (new Map())
            ->center(new Point(latitude: 5, longitude: 10))
            ->zoom(4);
        $attributes = ['data-foo' => 'bar'];

        $renderer = self::createMock(RendererInterface::class);
        $renderer
            ->expects(self::once())
            ->method('renderMap')
            ->with($map, $attributes)
            ->willReturn('<div data-controller="@symfony/ux-foobar-map"></div>')
        ;
        self::getContainer()->set('test.ux_map.renderers', $renderer);

        $twig = self::getContainer()->get('twig');
        $template = $twig->createTemplate('{{ ux_map(center: {lat: 5, lng: 10}, zoom: 4, attributes: attributes) }}');

        $this->assertSame(
            '<div data-controller="@symfony/ux-foobar-map"></div>',
            $template->render(['attributes' => $attributes]),
        );
    }
}
