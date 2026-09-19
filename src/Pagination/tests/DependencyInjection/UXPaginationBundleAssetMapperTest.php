<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Pagination\UXPaginationBundle;

#[CoversClass(UXPaginationBundle::class)]
final class UXPaginationBundleAssetMapperTest extends TestCase
{
    public function testPrependsPathsWithAssetMapperBundle(): void
    {
        $config = $this->prepend([
            'FrameworkBundle' => ['path' => __DIR__],
            'AssetMapperBundle' => ['path' => __DIR__],
        ]);

        self::assertCount(1, $config);
        self::assertSame([
            'asset_mapper' => [
                'paths' => [
                    \dirname(__DIR__, 2).'/assets/dist' => '@symfony/ux-pagination',
                ],
            ],
        ], $config[0]);
    }

    public function testPrependsPathsWithLegacyFrameworkBundle(): void
    {
        $filesystem = new Filesystem();
        $directory = sys_get_temp_dir().'/pagination-framework-'.bin2hex(random_bytes(6));
        $filesystem->dumpFile($directory.'/Resources/config/asset_mapper.php', '<?php');

        try {
            $config = $this->prepend(['FrameworkBundle' => ['path' => $directory]]);

            self::assertSame('@symfony/ux-pagination', array_values($config[0]['asset_mapper']['paths'])[0]);
        } finally {
            $filesystem->remove($directory);
        }
    }

    /**
     * @param array<string, array{path: string}> $bundles
     */
    #[DataProvider('provideUnavailableAssetMapper')]
    public function testDoesNotPrependWithoutAssetMapper(array $bundles): void
    {
        self::assertSame([], $this->prepend($bundles));
    }

    /**
     * @return iterable<string, array{array<string, array{path: string}>}>
     */
    public static function provideUnavailableAssetMapper(): iterable
    {
        yield 'no framework bundle' => [[]];
        yield 'no asset mapper configuration' => [['FrameworkBundle' => ['path' => __DIR__]]];
    }

    /**
     * @param array<string, array{path: string}> $bundles
     *
     * @return array<array<string, mixed>>
     */
    private function prepend(array $bundles): array
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles_metadata', $bundles);
        $configurator = new \ReflectionClass(ContainerConfigurator::class)->newInstanceWithoutConstructor();

        new UXPaginationBundle()->prependExtension($configurator, $container);

        return $container->getExtensionConfig('framework');
    }
}
