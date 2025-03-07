<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\Toolkit\DependencyInjection\ToolkitExtension;

/**
 * @author Jean-François Lépine
 */
class ToolkitExtensionTest extends TestCase
{
    public function testGetAlias(): void
    {
        $extension = new ToolkitExtension();
        $this->assertEquals('ux_toolkit', $extension->getAlias());
    }

    public function testLoadInjectUsefulParameters(): void
    {
        $configs = [
            'prefix' => 'Acme',
            'theme' => 'default',
        ];

        $container = new ContainerBuilder();
        $extension = new ToolkitExtension();
        $extension->load([$configs], $container);

        $this->assertTrue($container->hasParameter('ux_toolkit.prefix'));
        $this->assertEquals('Acme', $container->getParameter('ux_toolkit.prefix'));

        $this->assertTrue($container->hasParameter('ux_toolkit.theme'));
        $this->assertEquals('default', $container->getParameter('ux_toolkit.theme'));
    }
}
