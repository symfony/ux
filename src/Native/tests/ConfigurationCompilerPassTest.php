<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\UX\Native\ConfigurationBuilder;
use Symfony\UX\Native\ConfigurationCompilerPass;
use Symfony\UX\Native\Tests\Fixtures\InvalidReturnTypeConfigurationProvider;
use Symfony\UX\Native\Tests\Fixtures\StaticMethodConfigurationProvider;

final class ConfigurationCompilerPassTest extends TestCase
{
    public function testEarlyReturnWhenNoConfigurationBuilder()
    {
        $container = new ContainerBuilder();
        $pass = new ConfigurationCompilerPass();

        // Should not throw any exception
        $pass->process($container);

        self::assertFalse($container->hasDefinition('.ux_native.configuration_builder'));
    }

    public function testThrowsExceptionWhenPathIsMissing()
    {
        $container = new ContainerBuilder();
        $container->setDefinition('.ux_native.configuration_builder', new Definition(ConfigurationBuilder::class, ['/tmp']));

        $definition = new Definition(\stdClass::class);
        $definition->addTag('ux_native.configuration', []);
        $container->setDefinition('test.config_without_path', $definition);

        $pass = new ConfigurationCompilerPass();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The path is missing for "test.config_without_path"');

        $pass->process($container);
    }

    public function testThrowsExceptionForStaticMethod()
    {
        $container = new ContainerBuilder();
        $container->setDefinition('.ux_native.configuration_builder', new Definition(ConfigurationBuilder::class, ['/tmp']));

        $definition = new Definition(StaticMethodConfigurationProvider::class);
        $definition->addTag('ux_native.configuration_provider');
        $container->setDefinition('test.static_provider', $definition);

        $pass = new ConfigurationCompilerPass();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The method "Symfony\UX\Native\Tests\Fixtures\StaticMethodConfigurationProvider::v1()" annotated with #[AsNativeConfiguration] must not be static.');

        $pass->process($container);
    }

    public function testThrowsExceptionForWrongReturnType()
    {
        $container = new ContainerBuilder();
        $container->setDefinition('.ux_native.configuration_builder', new Definition(ConfigurationBuilder::class, ['/tmp']));

        $definition = new Definition(InvalidReturnTypeConfigurationProvider::class);
        $definition->addTag('ux_native.configuration_provider');
        $container->setDefinition('test.invalid_return_type_provider', $definition);

        $pass = new ConfigurationCompilerPass();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The method "Symfony\UX\Native\Tests\Fixtures\InvalidReturnTypeConfigurationProvider::v1()" annotated with #[AsNativeConfiguration] must have a return type of "Symfony\UX\Native\Configuration\Configuration".');

        $pass->process($container);
    }

    public function testRegistersAttributeConfigurations()
    {
        $container = new ContainerBuilder();
        $container->setDefinition('.ux_native.configuration_builder', new Definition(ConfigurationBuilder::class, ['/tmp']));

        $definition = new Definition(AndroidConfigurationFactory::class);
        $definition->addTag('ux_native.configuration_provider');
        $container->setDefinition('test.android_provider', $definition);

        $pass = new ConfigurationCompilerPass();
        $pass->process($container);

        // The child definition should be registered
        $childId = '.ux_native.configuration.AndroidConfigurationFactory.v1';
        self::assertTrue($container->hasDefinition($childId));

        $childDefinition = $container->getDefinition($childId);
        self::assertTrue($childDefinition->hasTag('ux_native.configuration'));

        $tags = $childDefinition->getTag('ux_native.configuration');
        self::assertSame('/config/android_v1.json', $tags[0]['path']);

        // The builder should have a method call registered for the tag
        $builderDefinition = $container->getDefinition('.ux_native.configuration_builder');
        $methodCalls = $builderDefinition->getMethodCalls();
        self::assertNotEmpty($methodCalls);
        self::assertSame('add', $methodCalls[0][0]);
    }

    public function testRegistersManualTaggedConfigurations()
    {
        $container = new ContainerBuilder();
        $container->setDefinition('.ux_native.configuration_builder', new Definition(ConfigurationBuilder::class, ['/tmp']));

        $definition = new Definition(\stdClass::class);
        $definition->addTag('ux_native.configuration', ['path' => '/config/ios_v1.json']);
        $container->setDefinition('test.ios_config', $definition);

        $pass = new ConfigurationCompilerPass();
        $pass->process($container);

        $builderDefinition = $container->getDefinition('.ux_native.configuration_builder');
        $methodCalls = $builderDefinition->getMethodCalls();
        self::assertCount(1, $methodCalls);
        self::assertSame('add', $methodCalls[0][0]);
        self::assertSame('/config/ios_v1.json', $methodCalls[0][1][0]);
    }
}
