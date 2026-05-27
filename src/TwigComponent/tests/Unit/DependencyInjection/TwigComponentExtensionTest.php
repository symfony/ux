<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\TwigBundle\DependencyInjection\Compiler\SafeClassPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\DependencyInjection\TwigComponentExtension;
use Symfony\UX\TwigComponent\Twig\TwigEnvironmentConfigurator;
use Symfony\UX\TwigComponent\TwigComponentBundle;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class TwigComponentExtensionTest extends TestCase
{
    public function testDataCollectorWithDebugMode()
    {
        $container = $this->createContainer();
        $container->setParameter('kernel.debug', true);
        $container->registerExtension(new TwigComponentExtension());
        $container->loadFromExtension('twig_component', [
            'defaults' => [],
            'anonymous_template_directory' => 'components/',
        ]);
        $this->compileContainer($container);

        $this->assertTrue($container->hasDefinition('ux.twig_component.data_collector'));
        $this->assertTrue($container->getDefinition('ux.twig_component.data_collector')->getArgument(2));
    }

    public function testDataCollectorWithCollectComponentsDisabled()
    {
        $container = $this->createContainer();
        $container->setParameter('kernel.debug', true);
        $container->registerExtension(new TwigComponentExtension());
        $container->loadFromExtension('twig_component', [
            'defaults' => [],
            'anonymous_template_directory' => 'components/',
            'profiler' => [
                'collect_components' => false,
            ],
        ]);
        $this->compileContainer($container);

        $this->assertTrue($container->hasDefinition('ux.twig_component.data_collector'));
        $this->assertFalse($container->getDefinition('ux.twig_component.data_collector')->getArgument(2));
    }

    public function testDataCollectorNotLoadedInProductionByDefault()
    {
        $container = $this->createContainer();
        $container->setParameter('kernel.debug', false);
        $container->registerExtension(new TwigComponentExtension());
        $container->loadFromExtension('twig_component', [
            'defaults' => [],
            'anonymous_template_directory' => 'components/',
        ]);
        $this->compileContainer($container);

        $this->assertFalse($container->hasDefinition('ux.twig_component.data_collector'));
    }

    public function testDataCollectorWithDebugModeCanBeDisabled()
    {
        $container = $this->createContainer();
        $container->setParameter('kernel.debug', true);
        $container->registerExtension(new TwigComponentExtension());
        $container->loadFromExtension('twig_component', [
            'defaults' => [],
            'anonymous_template_directory' => 'components/',
            'profiler' => false,
        ]);
        $this->compileContainer($container);

        $this->assertFalse($container->hasDefinition('ux.twig_component.data_collector'));
    }

    public function testSafeClassPassIntegration()
    {
        if (!class_exists(SafeClassPass::class)) {
            $this->markTestSkipped('Requires symfony/twig-bundle >= 8.1 with SafeClassPass support');
        }

        $container = $this->createContainer();
        $container->registerExtension(new TwigComponentExtension());
        $container->loadFromExtension('twig_component', [
            'defaults' => [],
            'anonymous_template_directory' => 'components/',
        ]);
        $this->compileContainer($container);

        $this->assertFalse($container->hasDefinition('ux.twig_component.twig.environment_configurator'));
        $this->assertTrue($container->hasDefinition(ComponentAttributes::class));
        $def = $container->getDefinition(ComponentAttributes::class);
        $this->assertTrue($def->hasTag('twig.safe_class'));
        $this->assertSame([['strategy' => 'html']], $def->getTag('twig.safe_class'));
    }

    public function testFallbackToEnvironmentConfiguratorWithoutSafeClassPass()
    {
        if (class_exists(SafeClassPass::class)) {
            $this->markTestSkipped('Only relevant with symfony/twig-bundle < 8.1 without SafeClassPass support');
        }

        $container = $this->createContainer();
        $container->registerExtension(new TwigComponentExtension());
        $container->loadFromExtension('twig_component', [
            'defaults' => [],
            'anonymous_template_directory' => 'components/',
        ]);
        $this->compileContainer($container);

        $this->assertTrue($container->hasDefinition('ux.twig_component.twig.environment_configurator'));
        $this->assertSame(TwigEnvironmentConfigurator::class, $container->getDefinition('ux.twig_component.twig.environment_configurator')->getClass());
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
                'TwigBundle' => new class {},
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
