<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\React\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\React\Tests\Kernel\TwigAppKernel;
use Symfony\UX\React\Twig\ReactComponentExtension;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
class ReactComponentExtensionTest extends TestCase
{
    public function testRenderComponent()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();

        /** @var ReactComponentExtension $extension */
        $extension = $kernel->getContainer()->get('test.twig.extension.react');

        $rendered = $extension->renderReactComponent(
            'SubDir/MyComponent',
            ['fullName' => 'Titouan Galopin']
        );

        $this->assertSame(
            'data-controller="symfony--ux-react--react" data-symfony--ux-react--react-component-value="SubDir&#x2F;MyComponent" data-symfony--ux-react--react-props-value="&#x7B;&quot;fullName&quot;&#x3A;&quot;Titouan&#x20;Galopin&quot;&#x7D;"',
            $rendered
        );
    }

    public function testRenderComponentWithoutProps()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();

        /** @var ReactComponentExtension $extension */
        $extension = $kernel->getContainer()->get('test.twig.extension.react');

        $rendered = $extension->renderReactComponent('SubDir/MyComponent');

        $this->assertSame(
            'data-controller="symfony--ux-react--react" data-symfony--ux-react--react-component-value="SubDir&#x2F;MyComponent"',
            $rendered
        );
    }
}
