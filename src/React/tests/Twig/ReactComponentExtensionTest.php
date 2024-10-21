<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\React\Tests\Twig;

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
            'data-controller="symfony--ux-react--react" data-symfony--ux-react--react-component-value="SubDir/MyComponent" data-symfony--ux-react--react-props-value="{&quot;fullName&quot;:&quot;Titouan Galopin&quot;}"',
            $rendered
        );
    }

    /**
     * @dataProvider provideOptions
     */
    public function testRenderComponentWithOptions(array $options, string|false $expected)
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();

        /** @var ReactComponentExtension $extension */
        $extension = $kernel->getContainer()->get('test.twig.extension.react');

        $rendered = $extension->renderReactComponent(
            'SubDir/MyComponent',
            ['fullName' => 'Titouan Galopin'],
            $options,
        );

        $this->assertStringContainsString('data-controller="symfony--ux-react--react" data-symfony--ux-react--react-component-value="SubDir/MyComponent" data-symfony--ux-react--react-props-value="{&quot;fullName&quot;:&quot;Titouan Galopin&quot;}"', $rendered);
        if (false === $expected) {
            $this->assertStringNotContainsString('data-symfony--ux-react--react-permanent-value', $rendered);
        } else {
            $this->assertStringContainsString($expected, $rendered);
        }
    }

    public static function provideOptions(): iterable
    {
        yield 'permanent' => [['permanent' => true], 'data-symfony--ux-react--react-permanent-value="true"'];
        yield 'not permanent' => [['permanent' => false], 'data-symfony--ux-react--react-permanent-value="false"'];
        yield 'permanent not bool' => [['permanent' => 12345], false];
        yield 'no permanent' => [[], false];
    }

    public function testRenderComponentWithoutProps()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();

        /** @var ReactComponentExtension $extension */
        $extension = $kernel->getContainer()->get('test.twig.extension.react');

        $rendered = $extension->renderReactComponent('SubDir/MyComponent');

        $this->assertSame(
            'data-controller="symfony--ux-react--react" data-symfony--ux-react--react-component-value="SubDir/MyComponent"',
            $rendered
        );
    }
}
