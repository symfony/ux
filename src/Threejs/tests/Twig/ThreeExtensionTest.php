<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Threejs\Tests;

use Symfony\UX\Threejs\Mesh;
use Symfony\UX\Threejs\Three;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Threejs\Geometry\Box;
use Symfony\UX\Threejs\Material\MeshBasic;
use Symfony\UX\Threejs\Tests\Kernel\TwigAppKernel;

/**
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 *
 * @internal
 */
class ThreeExtensionTest extends TestCase
{
    public function testRenderThree()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        $three = new Three(500, 500);

        $three->addMesh(
            new Mesh(
                geometry: new Box(1, 2, 3),
                material: new MeshBasic('green'),
            )
        );

        $rendered = $container->get('test.threejs.twig_extension')->renderThreejs(
            $three,
            ['data-controller' => 'mycontroller', 'class' => 'myclass']
        );

        $this->assertSame(
            '<div data-controller="symfony--ux-threejs--three" data-symfony--ux-threejs--three-three-value="{&quot;renderer&quot;:{&quot;scene&quot;:{&quot;material&quot;:{&quot;transparent&quot;:false,&quot;type&quot;:&quot;MeshBasic&quot;,&quot;color&quot;:null,&quot;opacity&quot;:1,&quot;map&quot;:&quot;&quot;,&quot;doubleSide&quot;:false},&quot;lights&quot;:[{&quot;type&quot;:&quot;Ambient&quot;,&quot;color&quot;:&quot;white&quot;,&quot;intensity&quot;:1}],&quot;meshes&quot;:[{&quot;geometry&quot;:{&quot;type&quot;:&quot;Box&quot;,&quot;width&quot;:1,&quot;height&quot;:2,&quot;depth&quot;:3},&quot;material&quot;:{&quot;transparent&quot;:false,&quot;type&quot;:&quot;MeshBasic&quot;,&quot;color&quot;:&quot;green&quot;,&quot;opacity&quot;:1,&quot;map&quot;:&quot;&quot;,&quot;doubleSide&quot;:false},&quot;animation&quot;:{&quot;rotation&quot;:{&quot;x&quot;:0,&quot;y&quot;:0,&quot;z&quot;:0},&quot;translation&quot;:{&quot;x&quot;:0,&quot;y&quot;:0,&quot;z&quot;:0},&quot;scale&quot;:{&quot;x&quot;:0,&quot;y&quot;:0,&quot;z&quot;:0},&quot;playClip&quot;:null},&quot;position&quot;:{&quot;x&quot;:0,&quot;y&quot;:0,&quot;z&quot;:0},&quot;angle&quot;:{&quot;x&quot;:0,&quot;y&quot;:0,&quot;z&quot;:0}}],&quot;models&quot;:[]},&quot;controls&quot;:true,&quot;cameras&quot;:[{&quot;position&quot;:{&quot;x&quot;:0,&quot;y&quot;:0,&quot;z&quot;:5},&quot;type&quot;:&quot;Perspective&quot;,&quot;fov&quot;:75,&quot;near&quot;:0.1,&quot;far&quot;:1000}],&quot;width&quot;:500,&quot;height&quot;:500}}"></div>',
            $rendered
        );
    }
}
