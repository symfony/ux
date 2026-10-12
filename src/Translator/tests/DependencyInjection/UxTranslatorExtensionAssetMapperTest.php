<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Translator\DependencyInjection\UxTranslatorExtension;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class UxTranslatorExtensionAssetMapperTest extends TestCase
{
    public function testPrependsPathsWithAssetMapperBundle(): void
    {
        $config = $this->prepend([
            'FrameworkBundle' => ['path' => __DIR__],
            'AssetMapperBundle' => ['path' => __DIR__],
        ]);

        self::assertCount(1, $config);
        self::assertSame('@symfony/ux-translator', array_values($config[0]['asset_mapper']['paths'])[0]);
    }

    public function testPrependsDumpDirectory(): void
    {
        $config = $this->prepend([
            'FrameworkBundle' => ['path' => __DIR__],
            'AssetMapperBundle' => ['path' => __DIR__],
        ]);

        self::assertSame('var/translations', $config[0]['asset_mapper']['paths']['%kernel.project_dir%/var/translations']);
    }

    public function testPrependsTheConfiguredDumpDirectory(): void
    {
        $config = $this->prepend([
            'FrameworkBundle' => ['path' => __DIR__],
            'AssetMapperBundle' => ['path' => __DIR__],
        ], ['dump_directory' => '%kernel.project_dir%/assets/translations']);
        $paths = $config[0]['asset_mapper']['paths'];

        self::assertSame('var/translations', $paths['%kernel.project_dir%/assets/translations']);
        self::assertArrayNotHasKey('%kernel.project_dir%/var/translations', $paths);
    }

    public function testPrependsPathsWithLegacyFrameworkBundle(): void
    {
        $filesystem = new Filesystem();
        $directory = sys_get_temp_dir().'/translator-framework-'.bin2hex(random_bytes(6));
        $filesystem->dumpFile($directory.'/Resources/config/asset_mapper.php', '<?php');

        try {
            $config = $this->prepend(['FrameworkBundle' => ['path' => $directory]]);

            self::assertSame('@symfony/ux-translator', array_values($config[0]['asset_mapper']['paths'])[0]);
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
     * @param array<string, mixed>               $uxTranslatorConfig
     *
     * @return array<array<string, mixed>>
     */
    private function prepend(array $bundles, array $uxTranslatorConfig = []): array
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles_metadata', $bundles);
        $container->prependExtensionConfig('ux_translator', $uxTranslatorConfig);
        new UxTranslatorExtension()->prepend($container);

        return $container->getExtensionConfig('framework');
    }
}
