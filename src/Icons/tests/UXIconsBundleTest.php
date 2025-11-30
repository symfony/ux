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
