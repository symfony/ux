<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\Image\Provider\NullProviderFactory;
use Symfony\UX\Image\Renderer\ImageRendererInterface;
use Symfony\UX\Image\Tests\Fixtures\FakeProviderFactory;
use Symfony\UX\Image\UXImageBundle;

final class UXImageExtensionTest extends TestCase
{
    private array $originalBridges;

    protected function setUp(): void
    {
        $this->originalBridges = UXImageBundle::$bridges;
    }

    protected function tearDown(): void
    {
        UXImageBundle::$bridges = $this->originalBridges;
    }

    public function testItRegistersTheRendererWithTheConfiguredFormats()
    {
        $container = $this->buildContainer(['provider' => 'fake://default', 'formats' => ['webp', 'jpeg']]);

        self::assertTrue($container->hasDefinition('ux_image.renderer'));
        self::assertSame(['webp', 'jpeg'], $container->getDefinition('ux_image.renderer')->getArgument(2));
    }

    public function testTheDefaultFormatsAreAvifWebpJpeg()
    {
        self::assertSame(['avif', 'webp', 'jpeg'], $this->buildContainer([])->getDefinition('ux_image.renderer')->getArgument(2));
    }

    public function testItFallsBackToTheNullProviderWhenNoDsnIsConfigured()
    {
        $container = $this->buildContainer([]);

        self::assertTrue($container->hasDefinition('ux_image.provider_factory.null'));
        self::assertSame(NullProviderFactory::class, $container->getDefinition('ux_image.provider_factory.null')->getClass());
        self::assertTrue($container->getDefinition('ux_image.provider_factory.null')->hasTag('ux_image.provider_factory'));
    }

    public function testItRegistersTheNullProviderEvenWhenADsnIsConfigured()
    {
        $container = $this->buildContainer(['provider' => 'fake://default']);

        self::assertTrue($container->hasDefinition('ux_image.provider_factory.null'));
    }

    public function testTheDefaultDsnReachesTheProviderService()
    {
        $container = $this->buildContainer([]);

        self::assertSame('null://null', $container->getDefinition('ux_image.provider')->getArgument(0));
    }

    public function testTheConfiguredDsnReachesTheProviderService()
    {
        $container = $this->buildContainer(['provider' => 'fake://default']);

        self::assertSame('fake://default', $container->getDefinition('ux_image.provider')->getArgument(0));
    }

    public function testTheRendererInterfaceIsAliasedToTheRendererService()
    {
        $container = $this->buildContainer([]);

        self::assertTrue($container->hasAlias(ImageRendererInterface::class));
        self::assertSame('ux_image.renderer', (string) $container->getAlias(ImageRendererInterface::class));
    }

    public function testItRegistersATaggedProviderFactoryForEachAvailableBridge()
    {
        UXImageBundle::$bridges = ['fake' => ['factory' => FakeProviderFactory::class]];

        $container = $this->buildContainer([]);

        self::assertTrue($container->hasDefinition('ux_image.provider_factory.fake'));
        self::assertSame(FakeProviderFactory::class, $container->getDefinition('ux_image.provider_factory.fake')->getClass());
        self::assertTrue($container->getDefinition('ux_image.provider_factory.fake')->hasTag('ux_image.provider_factory'));
    }

    private function buildContainer(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $bundle = new UXImageBundle();
        $bundle->getContainerExtension()->load([$config], $container);

        return $container;
    }
}
