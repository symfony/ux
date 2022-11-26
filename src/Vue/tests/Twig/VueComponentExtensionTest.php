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
            $kernel->getContainer()->get('test.twig'),
            'SubDir/MyComponent',
            ['fullName' => 'Titouan Galopin']
        );

        $this->assertSame(
            'data-controller="symfony--ux-vue--vue" data-symfony--ux-vue--vue-component-value="SubDir&#x2F;MyComponent" data-symfony--ux-vue--vue-props-value="&#x7B;&quot;fullName&quot;&#x3A;&quot;Titouan&#x20;Galopin&quot;&#x7D;"',
            $rendered
        );
    }

    public function testRenderComponentWithoutProps()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();

        /** @var VueComponentExtension $extension */
        $extension = $kernel->getContainer()->get('test.twig.extension.vue');

        $rendered = $extension->renderVueComponent($kernel->getContainer()->get('test.twig'), 'SubDir/MyComponent');

        $this->assertSame(
            'data-controller="symfony--ux-vue--vue" data-symfony--ux-vue--vue-component-value="SubDir&#x2F;MyComponent"',
            $rendered
        );
    }
}
