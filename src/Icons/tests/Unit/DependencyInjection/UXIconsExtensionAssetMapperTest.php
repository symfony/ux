<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\Icons\DependencyInjection\UXIconsExtension;

final class UXIconsExtensionAssetMapperTest extends TestCase
{
    public function testPrependsTheDistPathWithoutTheJavaScriptOption(): void
    {
        $config = $this->prepend([]);

        self::assertCount(1, $config);
        self::assertSame(['@symfony/ux-icons'], array_values($config[0]['asset_mapper']['paths']));
    }

    public function testPrependsTheDumpDirectoryWithTheJavaScriptOption(): void
    {
        $config = $this->prepend(['javascript' => ['dump_directory' => '/app/var/js-icons']]);

        self::assertCount(1, $config);
        self::assertSame('var/icons', $config[0]['asset_mapper']['paths']['/app/var/js-icons']);
        self::assertContains('@symfony/ux-icons', $config[0]['asset_mapper']['paths']);
    }

    public function testDoesNotPrependWithoutAssetMapper(): void
    {
        self::assertSame([], $this->prepend(['javascript' => true], ['FrameworkBundle' => ['path' => __DIR__]]));
    }

    /**
     * @param array<string, mixed>               $iconsConfig
     * @param array<string, array{path: string}> $bundles
     *
     * @return array<array<string, mixed>>
     */
    private function prepend(array $iconsConfig, array $bundles = ['FrameworkBundle' => ['path' => __DIR__], 'AssetMapperBundle' => ['path' => __DIR__]]): array
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles_metadata', $bundles);
        $container->prependExtensionConfig('ux_icons', $iconsConfig);
        new UXIconsExtension()->prepend($container);

        return $container->getExtensionConfig('framework');
    }
}
