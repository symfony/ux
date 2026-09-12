<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Inspector\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\Inspector\UXInspectorBundle;

#[CoversClass(UXInspectorBundle::class)]
final class ConfigurationTest extends TestCase
{
    private function buildContainer(array $config = [], bool $debug = true): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'dev');
        $container->setParameter('kernel.debug', $debug);
        $container->setParameter('kernel.build_dir', sys_get_temp_dir().'/build');
        $container->setParameter('kernel.project_dir', \dirname(__DIR__, 2));
        $bundle = new UXInspectorBundle();
        $container->registerExtension($bundle->getContainerExtension());
        $container->loadFromExtension('ux_inspector', $config);
        // Do not compile: we want to inspect definitions before they are removed
        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        return $container;
    }

    public function testDefaultConfiguration(): void
    {
        $container = $this->buildContainer();

        self::assertTrue($container->hasDefinition(\Symfony\UX\Inspector\EventListener\InjectInspectorListener::class));
    }

    public function testCustomEnabledValue(): void
    {
        $container = $this->buildContainer(['enabled' => false]);

        self::assertTrue($container->hasDefinition(\Symfony\UX\Inspector\EventListener\InjectInspectorListener::class));
    }

    public function testCustomExcludePathsValues(): void
    {
        $container = $this->buildContainer([
            'exclude_paths' => ['/api', '/admin'],
        ]);

        self::assertTrue($container->hasDefinition(\Symfony\UX\Inspector\EventListener\InjectInspectorListener::class));
    }

    public function testCustomIgnoreSelectorsValues(): void
    {
        $container = $this->buildContainer([
            'ignore_selectors' => ['.no-inspect', '[data-skip]'],
        ]);

        self::assertTrue($container->hasDefinition(\Symfony\UX\Inspector\EventListener\InjectInspectorListener::class));
    }

    public function testThemeSettingIsNotSupported(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->buildContainer(['theme' => 'light']);
    }

    public function testPullTabMustBeBoolean(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->buildContainer(['pull_tab' => 'hidden']);
    }
}
