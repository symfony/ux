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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\Inspector\Controller\InspectorController;
use Symfony\UX\Inspector\EventListener\InjectInspectorListener;
use Symfony\UX\Inspector\Routing\InspectorRouteLoader;
use Symfony\UX\Inspector\UXInspectorBundle;

#[CoversClass(UXInspectorBundle::class)]
final class UXInspectorExtensionTest extends TestCase
{
    private function createContainer(array $config = [], bool $debug = true): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'dev');
        $container->setParameter('kernel.debug', $debug);
        $container->setParameter('kernel.build_dir', sys_get_temp_dir().'/build');
        $bundle = new UXInspectorBundle();
        $container->registerExtension($bundle->getContainerExtension());
        $container->loadFromExtension('ux_inspector', $config);
        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        return $container;
    }

    public function testRegistersListenerService(): void
    {
        $container = $this->createContainer();

        self::assertTrue($container->hasDefinition(InjectInspectorListener::class));
    }

    public function testListenerIsConfiguredWithDefaults(): void
    {
        $container = $this->createContainer();
        $definition = $container->getDefinition(InjectInspectorListener::class);

        self::assertNotNull($definition);
    }

    public function testDisabledConfig(): void
    {
        $container = $this->createContainer(['enabled' => false]);

        self::assertTrue($container->hasDefinition(InjectInspectorListener::class));
    }

    public function testExcludePathsDefault(): void
    {
        $container = $this->createContainer();
        $definition = $container->getDefinition(InjectInspectorListener::class);

        $excludePaths = $definition->getArgument('$excludePaths');
        self::assertSame(['/_profiler', '/_wdt', '/_error', '/_fragment'], $excludePaths);
    }

    public function testCustomExcludePaths(): void
    {
        $container = $this->createContainer([
            'exclude_paths' => ['/_profiler', '/api'],
        ]);
        $definition = $container->getDefinition(InjectInspectorListener::class);

        $excludePaths = $definition->getArgument('$excludePaths');
        self::assertSame(['/_profiler', '/api'], $excludePaths);
    }

    public function testIgnoreSelectorsDefault(): void
    {
        $container = $this->createContainer();
        $definition = $container->getDefinition(InjectInspectorListener::class);

        $config = $definition->getArgument('$config');
        self::assertSame([], $config['ignore_selectors']);
    }

    public function testPullTabIsVisibleByDefault(): void
    {
        $definition = $this->createContainer()->getDefinition(InjectInspectorListener::class);

        self::assertTrue($definition->getArgument('$config')['pull_tab']);
    }

    public function testPullTabCanBeHiddenWithoutDisablingInspector(): void
    {
        $definition = $this->createContainer(['pull_tab' => false])->getDefinition(InjectInspectorListener::class);

        self::assertFalse($definition->getArgument('$config')['pull_tab']);
        self::assertTrue($definition->getArgument('$enabled'));
    }

    public function testCustomIgnoreSelectors(): void
    {
        $container = $this->createContainer([
            'ignore_selectors' => ['.no-inspect', '[data-no-inspect]'],
        ]);
        $definition = $container->getDefinition(InjectInspectorListener::class);

        $config = $definition->getArgument('$config');
        self::assertSame(['.no-inspect', '[data-no-inspect]'], $config['ignore_selectors']);
    }

    public function testBundlePath(): void
    {
        $bundle = new UXInspectorBundle();

        self::assertSame(\dirname(__DIR__, 2), $bundle->getPath());
    }

    public function testBundleExtendsAbstractBundle(): void
    {
        $bundle = new UXInspectorBundle();

        self::assertInstanceOf(\Symfony\Component\HttpKernel\Bundle\AbstractBundle::class, $bundle);
    }

    public function testContainerExtensionAlias(): void
    {
        $bundle = new UXInspectorBundle();

        self::assertSame('ux_inspector', $bundle->getContainerExtension()->getAlias());
    }

    public function testRegistersAssetControllerOnlyWhenEnabled(): void
    {
        self::assertTrue($this->createContainer()->hasDefinition(InspectorController::class));
        self::assertFalse($this->createContainer(debug: false)->hasDefinition(InspectorController::class));
        self::assertFalse($this->createContainer(['enabled' => false])->hasDefinition(InspectorController::class));
    }

    public function testDisablesRouteLoaderWithDebug(): void
    {
        $definition = $this->createContainer(debug: false)->getDefinition(InspectorRouteLoader::class);

        self::assertFalse($definition->getArgument('$enabled'));
    }

    public function testListenerDisabledWhenKernelDebugIsFalse(): void
    {
        $container = $this->createContainer(['enabled' => true], debug: false);
        $definition = $container->getDefinition(InjectInspectorListener::class);

        self::assertFalse($definition->getArgument('$enabled'));
    }

    public function testListenerEnabledWhenKernelDebugIsTrue(): void
    {
        $container = $this->createContainer(['enabled' => true], debug: true);
        $definition = $container->getDefinition(InjectInspectorListener::class);

        self::assertTrue($definition->getArgument('$enabled'));
    }

    public function testListenerDisabledWhenConfigDisabledEvenWithDebug(): void
    {
        $container = $this->createContainer(['enabled' => false], debug: true);
        $definition = $container->getDefinition(InjectInspectorListener::class);

        self::assertFalse($definition->getArgument('$enabled'));
    }

    public function testListenerReceivesConfigArguments(): void
    {
        $container = $this->createContainer([
            'ignore_selectors' => ['.skip-me'],
        ]);
        $definition = $container->getDefinition(InjectInspectorListener::class);

        $config = $definition->getArgument('$config');
        self::assertArrayNotHasKey('theme', $config);
        self::assertSame(['.skip-me'], $config['ignore_selectors']);
        self::assertSame([
            'stimulus' => class_exists('Symfony\\UX\\StimulusBundle\\StimulusBundle'),
            'livecomponent' => class_exists('Symfony\\UX\\LiveComponent\\LiveComponentBundle'),
            'turbo' => class_exists('Symfony\\UX\\Turbo\\TurboBundle'),
        ], $config['packages']);
    }
}
