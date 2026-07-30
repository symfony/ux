<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\Icons\DependencyInjection\UXIconsExtension;

class UXIconsBundleTest extends TestCase
{
    public static function provideTestInvalidAliasConfiguration(): iterable
    {
        yield ['', 'Invalid type for path "ux_icons.aliases". Expected "array", but got "string"'];
        yield [123, 'Invalid type for path "ux_icons.aliases". Expected "array", but got "int'];
        yield [false, 'Invalid type for path "ux_icons.aliases". Expected "array", but got "bool"'];

        if (method_exists(ArrayNodeDefinition::class, 'stringPrototype')) {
            yield [[1, 2, 3], 'Invalid type for path "ux_icons.aliases.0". Expected "string", but got "int"'];
            yield [['foo' => 123], 'Invalid type for path "ux_icons.aliases.foo". Expected "string", but got "int"'];
        }
    }

    /**
     * @dataProvider provideTestInvalidAliasConfiguration
     */
    #[DataProvider('provideTestInvalidAliasConfiguration')]
    public function testInvalidAliasConfiguration(mixed $value, string $expectedMessage)
    {
        self::expectException(InvalidConfigurationException::class);
        self::expectExceptionMessage($expectedMessage);

        $processor = new Processor();
        $configurableExtension = new UXIconsExtension();
        $processor->processConfiguration($configurableExtension, [
            [
                'aliases' => $value,
            ],
        ]);
    }

    public static function provideTestValidAliasConfiguration(): iterable
    {
        yield [[]];
        yield [['foo' => 'bar']];
    }

    /**
     * @dataProvider provideTestValidAliasConfiguration
     */
    #[DataProvider('provideTestValidAliasConfiguration')]
    public function testValidAliasConfiguration(array $value)
    {
        $processor = new Processor();
        $configurableExtension = new UXIconsExtension();
        $processedConfig = $processor->processConfiguration($configurableExtension, [
            [
                'aliases' => $value,
            ],
        ]);

        $this->assertSame($value, $processedConfig['aliases']);
    }

    public static function provideTestInvalidIconAttributesConfiguration(): iterable
    {
        yield ['', 'Invalid type for path "ux_icons.default_icon_attributes". Expected "array", but got "string"'];
        yield [123, 'Invalid type for path "ux_icons.default_icon_attributes". Expected "array", but got "int"'];
        yield [false, 'Invalid type for path "ux_icons.default_icon_attributes". Expected "array", but got "bool"'];
    }

    /**
     * @dataProvider provideTestInvalidIconAttributesConfiguration
     */
    #[DataProvider('provideTestInvalidIconAttributesConfiguration')]
    public function testInvalidIconAttributeConfiguration(mixed $value, string $expectedMessage)
    {
        self::expectException(InvalidConfigurationException::class);
        self::expectExceptionMessage($expectedMessage);

        $processor = new Processor();
        $configurableExtension = new UXIconsExtension();
        $processor->processConfiguration($configurableExtension, [
            [
                'default_icon_attributes' => $value,
            ],
        ]);
    }

    private function buildContainer(array $config = []): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', []);
        $container->setParameter('kernel.project_dir', __DIR__);

        new UXIconsExtension()->load([$config], $container);

        return $container;
    }

    public function testAutoLockDefaultsToFalse()
    {
        $config = new Processor()->processConfiguration(new UXIconsExtension(), [[]]);

        $this->assertFalse($config['iconify']['auto_lock']);
    }

    public function testAutoLockRegistryIsWiredWhenEnabled()
    {
        $container = $this->buildContainer(['iconify' => ['auto_lock' => true]]);

        $this->assertTrue($container->hasDefinition('.ux_icons.auto_lock_icon_registry'));
        $this->assertTrue($container->getDefinition('.ux_icons.auto_lock_icon_registry')->hasTag('ux_icons.registry'));
        $this->assertSame([['priority' => -10]], $container->getDefinition('.ux_icons.auto_lock_icon_registry')->getTag('ux_icons.registry'));
        // the raw on-demand registry drops out of the chain; the decorator takes its slot
        $this->assertFalse($container->getDefinition('.ux_icons.iconify_on_demand_registry')->hasTag('ux_icons.registry'));
    }

    public function testAutoLockRegistryIsRemovedWhenDisabled()
    {
        $container = $this->buildContainer(['iconify' => ['auto_lock' => false]]);

        $this->assertFalse($container->hasDefinition('.ux_icons.auto_lock_icon_registry'));
        $this->assertTrue($container->getDefinition('.ux_icons.iconify_on_demand_registry')->hasTag('ux_icons.registry'));
    }

    public function testAutoLockRegistryIsRemovedWhenOnDemandDisabled()
    {
        $container = $this->buildContainer(['iconify' => ['on_demand' => false]]);

        $this->assertFalse($container->hasDefinition('.ux_icons.auto_lock_icon_registry'));
        $this->assertFalse($container->hasDefinition('.ux_icons.iconify_on_demand_registry'));
    }

    public static function provideTestValidIconAttributesConfiguration(): iterable
    {
        yield [[]];
        yield [['class' => 'icon-large', 'aria-hidden' => 'true', 'data-test' => 123]];
    }

    /**
     * @dataProvider provideTestValidIconAttributesConfiguration
     */
    #[DataProvider('provideTestValidIconAttributesConfiguration')]
    public function testValidIconAttributeConfiguration(array $value)
    {
        $processor = new Processor();
        $configurableExtension = new UXIconsExtension();
        $processedConfig = $processor->processConfiguration($configurableExtension, [
            [
                'default_icon_attributes' => $value,
            ],
        ]);
        $this->assertSame($value, $processedConfig['default_icon_attributes']);
    }
}
