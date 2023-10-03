<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Test\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\UX\TwigComponent\DependencyInjection\TwigComponentExtension;
use Symfony\UX\TwigComponent\TwigComponentBundle;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class TwigComponentExtensionTest extends TestCase
{
    public function testDataCollectorWithDebugMode()
    {
        $container = $this->createContainer();
        $container->registerExtension(new TwigComponentExtension());
        $container->loadFromExtension('twig_component', []);
        $this->compileContainer($container);

        $this->assertTrue($container->hasDefinition('ux.twig_component.data_collector'));
    }

    public function testDataCollectorWithoutDebugMode()
    {
        $container = $this->createContainer();
        $container->setParameter('kernel.debug', false);
        $container->registerExtension(new TwigComponentExtension());
        $container->loadFromExtension('twig_component', []);
        $this->compileContainer($container);

        $this->assertFalse($container->hasDefinition('ux.twig_component.data_collector'));
    }

    private function createContainer()
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.cache_dir' => __DIR__,
            'kernel.build_dir' => __DIR__,
            'kernel.charset' => 'UTF-8',
            'kernel.debug' => true,
            'kernel.project_dir' => __DIR__,
            'kernel.bundles' => [
                'TwigBundle' => new class() {},
                'TwigComponentBundle' => TwigComponentBundle::class,
            ],
        ]));

        return $container;
    }

    private function compileContainer(ContainerBuilder $container)
    {
        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->getCompilerPassConfig()->setAfterRemovingPasses([]);
        $container->compile();
    }
}
