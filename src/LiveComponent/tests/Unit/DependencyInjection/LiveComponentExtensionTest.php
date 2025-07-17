<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\UX\LiveComponent\DependencyInjection\LiveComponentExtension;

class LiveComponentExtensionTest extends TestCase
{
    public function testKernelSecretIsUsedByDefault(): void
    {
        $container = $this->createContainer();
        $container->registerExtension(new LiveComponentExtension());
        $container->loadFromExtension('live_component', []);
        $this->compileContainer($container);

        $this->assertSame('%kernel.secret%', $container->getDefinition('ux.live_component.component_hydrator')->getArgument(4));
        $this->assertSame('%kernel.secret%', $container->getDefinition('ux.live_component.fingerprint_calculator')->getArgument(0));
    }

    public function testCustomSecretIsUsedInDefinition(): void
    {
        $container = $this->createContainer();
        $container->registerExtension(new LiveComponentExtension());
        $container->loadFromExtension('live_component', [
            'secret' => 'custom_secret',
        ]);
        $this->compileContainer($container);

        $this->assertSame('custom_secret', $container->getDefinition('ux.live_component.component_hydrator')->getArgument(4));
        $this->assertSame('custom_secret', $container->getDefinition('ux.live_component.fingerprint_calculator')->getArgument(0));
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.cache_dir' => __DIR__,
            'kernel.project_dir' => __DIR__,
            'kernel.charset' => 'UTF-8',
            'kernel.debug' => false,
            'kernel.bundles' => [],
            'kernel.bundles_metadata' => [],
        ]));

        return $container;
    }

    private function compileContainer(ContainerBuilder $container): void
    {
        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->getCompilerPassConfig()->setAfterRemovingPasses([]);
        $container->compile();
    }
}
