<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\UX\Pagination\UXPaginationBundle;

#[CoversClass(UXPaginationBundle::class)]
final class UXPaginationBundleTest extends TestCase
{
    public function testGetContainerExtensionReturnsExtensionWithCorrectAlias(): void
    {
        $bundle = new UXPaginationBundle();
        $extension = $bundle->getContainerExtension();

        self::assertNotNull($extension);
        self::assertSame('ux_pagination', $extension->getAlias());
    }

    public function testGetPathReturnsParentDirectory(): void
    {
        $bundle = new UXPaginationBundle();
        $path = $bundle->getPath();

        self::assertSame(\dirname(__DIR__), $path);
        self::assertDirectoryExists($path);
        self::assertFileExists($path.'/composer.json');
    }

    public function testPrependsAssetMapperPathOnlyWhenAvailable(): void
    {
        $container = new ContainerBuilder();
        $configurator = new \ReflectionClass(ContainerConfigurator::class)->newInstanceWithoutConstructor();

        new UXPaginationBundle()->prependExtension($configurator, $container);

        if (!interface_exists(\Symfony\Component\AssetMapper\AssetMapperInterface::class)) {
            self::assertSame([], $container->getExtensionConfig('framework'));

            return;
        }

        self::assertSame([
            'asset_mapper' => [
                'paths' => [
                    \dirname(__DIR__).'/assets/dist' => '@symfony/ux-pagination',
                ],
            ],
        ], $container->getExtensionConfig('framework')[0]);
    }
}
