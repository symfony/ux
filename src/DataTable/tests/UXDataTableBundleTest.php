<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\MergeExtensionConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\DataTable\DataTableFactory;
use Symfony\UX\DataTable\DataTableType;
use Symfony\UX\DataTable\TypeInterface;
use Symfony\UX\DataTable\UXDataTableBundle;

final class UXDataTableBundleTest extends TestCase
{
    public function testRegistersTheServices(): void
    {
        $container = self::load([]);

        self::assertTrue($container->hasDefinition('ux_data_table.factory'));
        self::assertSame('ux_data_table.factory', (string) $container->getAlias(DataTableFactory::class));
        self::assertTrue($container->hasDefinition('ux_data_table.twig.extension'));
        self::assertSame('@UXDataTable/theme/default.html.twig', $container->getDefinition(DataTableType::class)->getArgument('$theme'));
        self::assertTrue($container->getDefinition(DataTableType::class)->hasTag('ux_data_table.type'));
        self::assertArrayHasKey('ux_data_table.type', $container->getAutoconfiguredInstanceof()[TypeInterface::class]->getTags());
    }

    public function testConfiguresTheTheme(): void
    {
        self::assertSame('theme.html.twig', self::load(['theme' => 'theme.html.twig'])->getDefinition(DataTableType::class)->getArgument('$theme'));
    }

    public function testRejectsAnEmptyTheme(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        self::load(['theme' => '']);
    }

    public function testHasItsDirectoryAsPath(): void
    {
        self::assertSame(\dirname(__DIR__), new UXDataTableBundle()->getPath());
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function load(array $config): ContainerBuilder
    {
        $bundle = new UXDataTableBundle();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir());
        $container->registerExtension($bundle->getContainerExtension());
        $container->loadFromExtension('ux_data_table', $config);
        $bundle->build($container);
        new MergeExtensionConfigurationPass()->process($container);

        return $container;
    }
}
