<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Svelte\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Svelte\Tests\Kernel\TwigAppKernel;
use Symfony\UX\Svelte\Twig\SvelteComponentExtension;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Thomas Choquet <thomas.choquet.pro@gmail.com>
 *
 * @internal
 */
class SvelteComponentExtensionTest extends TestCase
{
    public function testRenderComponent()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();

        /** @var SvelteComponentExtension $extension */
        $extension = $kernel->getContainer()->get('test.twig.extension.svelte');

        $rendered = $extension->renderSvelteComponent(
            $kernel->getContainer()->get('test.twig'),
            'SubDir/MyComponent',
            ['fullName' => 'Titouan Galopin']
        );

        $this->assertSame(
            'data-controller="symfony--ux-svelte--svelte" data-symfony--ux-svelte--svelte-component-value="SubDir&#x2F;MyComponent" data-symfony--ux-svelte--svelte-props-value="&#x7B;&quot;fullName&quot;&#x3A;&quot;Titouan&#x20;Galopin&quot;&#x7D;"',
            $rendered
        );
    }

    public function testRenderComponentWithoutProps()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();

        /** @var SvelteComponentExtension $extension */
        $extension = $kernel->getContainer()->get('test.twig.extension.svelte');

        $rendered = $extension->renderSvelteComponent($kernel->getContainer()->get('test.twig'), 'SubDir/MyComponent');

        $this->assertSame(
            'data-controller="symfony--ux-svelte--svelte" data-symfony--ux-svelte--svelte-component-value="SubDir&#x2F;MyComponent"',
            $rendered
        );
    }

    public function testRenderComponentWithIntro()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();

        /** @var SvelteComponentExtension $extension */
        $extension = $kernel->getContainer()->get('test.twig.extension.svelte');

        $rendered = $extension->renderSvelteComponent(
            $kernel->getContainer()->get('test.twig'),
            'SubDir/MyComponent',
            ['fullName' => 'Titouan Galopin'],
            true
        );

        $this->assertSame(
            'data-controller="symfony--ux-svelte--svelte" data-symfony--ux-svelte--svelte-component-value="SubDir&#x2F;MyComponent" data-symfony--ux-svelte--svelte-props-value="&#x7B;&quot;fullName&quot;&#x3A;&quot;Titouan&#x20;Galopin&quot;&#x7D;" data-symfony--ux-svelte--svelte-intro-value="true"',
            $rendered
        );
    }
}
