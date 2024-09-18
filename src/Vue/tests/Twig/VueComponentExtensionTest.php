<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Vue\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Vue\Tests\Kernel\TwigAppKernel;
use Symfony\UX\Vue\Twig\VueComponentExtension;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Thibault RICHARD <thibault.richard62@gmail.com>
 *
 * @internal
 */
class VueComponentExtensionTest extends TestCase
{
    public function testRenderComponent()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();

        /** @var VueComponentExtension $extension */
        $extension = $kernel->getContainer()->get('test.twig.extension.vue');

        $rendered = $extension->renderVueComponent(
            'SubDir/MyComponent',
            ['fullName' => 'Titouan Galopin']
        );

        $this->assertSame(
            'data-controller="symfony--ux-vue--vue" data-symfony--ux-vue--vue-component-value="SubDir/MyComponent" data-symfony--ux-vue--vue-props-value="{&quot;fullName&quot;:&quot;Titouan Galopin&quot;}"',
            $rendered
        );
    }

    public function testRenderComponentWithoutProps()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();

        /** @var VueComponentExtension $extension */
        $extension = $kernel->getContainer()->get('test.twig.extension.vue');

        $rendered = $extension->renderVueComponent('SubDir/MyComponent');

        $this->assertSame(
            'data-controller="symfony--ux-vue--vue" data-symfony--ux-vue--vue-component-value="SubDir/MyComponent"',
            $rendered
        );
    }
}
