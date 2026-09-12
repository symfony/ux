<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\StimulusBundle\DependencyInjection\StimulusExtension;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class StimulusExtensionTest extends TestCase
{
    public function testPrependsPathsWithAssetMapperBundle(): void
    {
        $config = $this->prepend([
            'FrameworkBundle' => ['path' => __DIR__],
            'AssetMapperBundle' => ['path' => __DIR__],
        ]);

        self::assertCount(1, $config);
        self::assertSame('@symfony/stimulus-bundle', array_values($config[0]['asset_mapper']['paths'])[0]);
    }

    public function testPrependsPathsWithLegacyFrameworkBundle(): void
    {
        $filesystem = new Filesystem();
        $directory = sys_get_temp_dir().'/stimulus-framework-'.bin2hex(random_bytes(6));
        $filesystem->dumpFile($directory.'/Resources/config/asset_mapper.php', '<?php');

        try {
            $config = $this->prepend(['FrameworkBundle' => ['path' => $directory]]);

            self::assertSame('@symfony/stimulus-bundle', array_values($config[0]['asset_mapper']['paths'])[0]);
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

    public static function provideUnavailableAssetMapper(): iterable
    {
        yield 'no framework bundle' => [[]];
        yield 'no asset mapper configuration' => [['FrameworkBundle' => ['path' => __DIR__]]];
    }

    /**
     * @param array<string, array{path: string}> $bundles
     *
     * @return list<array<string, mixed>>
     */
    private function prepend(array $bundles): array
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles_metadata', $bundles);
        new StimulusExtension()->prepend($container);

        return $container->getExtensionConfig('framework');
    }
}
