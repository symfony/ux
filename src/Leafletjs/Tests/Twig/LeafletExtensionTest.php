<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Leafletjs\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Leafletjs\Builder\LeafletBuilderInterface;
use Symfony\UX\Leafletjs\Tests\Kernel\TwigAppKernel;
use Twig\Environment;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Michael Cramer <michael@bigmichi1.de>
 *
 * @internal
 */
class LeafletExtensionTest extends TestCase
{
    public function testRenderMap()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        /** @var LeafletBuilderInterface $builder */
        $builder = $container->get('test.leafletjs.builder');

        $map = $builder->createMap(50, 0);
        $map->setMapOptions(['minZoom' => 1, 'maxZoom' => 8]);

        $rendered = $container->get('test.leafletjs.twig_extension')->renderMap(
            $container->get(Environment::class),
            $map,
            ['data-controller' => 'mycontroller', 'class' => 'myclass']
        );

        $this->assertSame(
            '<div
                data-controller="mycontroller @symfony/ux-leafletjs/map"
                data-leafletjs-target="placeholder"
                data-leafletjs-longitude="50"
                data-leafletjs-latitude="0"
                data-leafletjs-zoom="10"
                data-leafletjs-map-options="&#x7B;&quot;minZoom&quot;&#x3A;1,&quot;maxZoom&quot;&#x3A;8&#x7D;"
        class="myclass"></div>',
            $rendered
        );
    }
}
