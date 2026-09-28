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
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\UX\Image\ImageUrlGenerator;
use Symfony\UX\Image\Provider\NullProviderFactory;
use Symfony\UX\Image\Renderer\ImageRendererInterface;
use Symfony\UX\Image\Renderer\LayoutResolver;
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

    public function testItRegistersTheRendererWithTheConfiguredFormats(): void
    {
        $container = $this->buildContainer(['provider' => 'fake://default', 'formats' => ['webp', 'jpeg']]);

        self::assertTrue($container->hasDefinition('ux_image.renderer'));
        self::assertSame(['webp', 'jpeg'], $container->getDefinition('ux_image.renderer')->getArgument(2));
    }

    public function testTheDefaultFormatsAreAvifWebpJpeg(): void
    {
        self::assertSame(['avif', 'webp', 'jpeg'], $this->buildContainer([])->getDefinition('ux_image.renderer')->getArgument(2));
    }

    public function testItRegistersTheLayoutResolverWithTheConfiguredResolutions(): void
    {
        $container = $this->buildContainer(['provider' => 'fake://default', 'resolutions' => [1600, 800, 400]]);

        self::assertTrue($container->hasDefinition('ux_image.layout_resolver'));
        self::assertSame([1600, 800, 400], $container->getDefinition('ux_image.layout_resolver')->getArgument(0));
    }

    public function testTheDefaultResolutionsAreTheUnpicLadder(): void
    {
        self::assertSame(LayoutResolver::DEFAULT_RESOLUTIONS, $this->buildContainer([])->getDefinition('ux_image.layout_resolver')->getArgument(0));
    }

    public function testItFallsBackToTheNullProviderWhenNoDsnIsConfigured(): void
    {
        $container = $this->buildContainer([]);

        self::assertTrue($container->hasDefinition('ux_image.provider_factory.null'));
        self::assertSame(NullProviderFactory::class, $container->getDefinition('ux_image.provider_factory.null')->getClass());
        self::assertTrue($container->getDefinition('ux_image.provider_factory.null')->hasTag('ux_image.provider_factory'));
    }

    public function testItRegistersTheNullProviderEvenWhenADsnIsConfigured(): void
    {
        $container = $this->buildContainer(['provider' => 'fake://default']);

        self::assertTrue($container->hasDefinition('ux_image.provider_factory.null'));
    }

    public function testTheDefaultDsnReachesTheProviderService(): void
    {
        $container = $this->buildContainer([]);

        self::assertSame('null://null', $container->getDefinition('ux_image.provider')->getArgument(0));
    }

    public function testTheConfiguredDsnReachesTheProviderService(): void
    {
        $container = $this->buildContainer(['provider' => 'fake://default']);

        self::assertSame('fake://default', $container->getDefinition('ux_image.provider')->getArgument(0));
    }

    public function testTheRendererInterfaceIsAliasedToTheRendererService(): void
    {
        $container = $this->buildContainer([]);

        self::assertTrue($container->hasAlias(ImageRendererInterface::class));
        self::assertSame('ux_image.renderer', (string) $container->getAlias(ImageRendererInterface::class));
    }

    public function testItRegistersATaggedProviderFactoryForEachAvailableBridge(): void
    {
        UXImageBundle::$bridges = ['fake' => ['factory' => FakeProviderFactory::class]];

        $container = $this->buildContainer([]);

        self::assertTrue($container->hasDefinition('ux_image.provider_factory.fake'));
        self::assertSame(FakeProviderFactory::class, $container->getDefinition('ux_image.provider_factory.fake')->getClass());
        self::assertTrue($container->getDefinition('ux_image.provider_factory.fake')->hasTag('ux_image.provider_factory'));
    }

    public function testProviderFactoriesAreTaggedWithTheirName(): void
    {
        UXImageBundle::$bridges = ['fake' => ['factory' => FakeProviderFactory::class]];

        $container = $this->buildContainer([]);

        self::assertSame([['provider' => 'null']], $container->getDefinition('ux_image.provider_factory.null')->getTag('ux_image.provider_factory'));
        self::assertSame([['provider' => 'fake']], $container->getDefinition('ux_image.provider_factory.fake')->getTag('ux_image.provider_factory'));
    }

    public function testTheUrlGeneratorIsAutowirable(): void
    {
        $container = $this->buildContainer([]);

        self::assertSame('ux_image.url_generator', (string) $container->getAlias(ImageUrlGenerator::class));
        self::assertSame(ImageUrlGenerator::class, $container->getDefinition('ux_image.url_generator')->getClass());
    }

    public function testTheConfiguredQualityReachesTheUrlGenerator(): void
    {
        $container = $this->buildContainer(['quality' => 75]);

        self::assertSame(75, $container->getDefinition('ux_image.url_generator')->getArgument('$defaultQuality'));
    }

    public function testTheQualityDefaultsToTheProviders(): void
    {
        self::assertNull($this->buildContainer([])->getDefinition('ux_image.url_generator')->getArgument('$defaultQuality'));
    }

    public function testAQualityOutOfRangeIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The value 0 is too small for path "ux_image.quality".');

        $this->buildContainer(['quality' => 0]);
    }

    public function testThePresetsDefaultToNone(): void
    {
        $definition = $this->buildContainer([])->getDefinition('.ux_image.presets');

        self::assertSame([], $definition->getArgument(0));
    }

    public function testTheUrlGeneratorAndTheRendererShareThePresetsService(): void
    {
        $container = $this->buildContainer([]);

        self::assertSame('.ux_image.presets', (string) $container->getDefinition('ux_image.url_generator')->getArgument('$presets'));
        self::assertSame('.ux_image.presets', (string) $container->getDefinition('ux_image.renderer')->getArgument(4));
    }

    public function testTheConfiguredPresetsReachThePresetsService(): void
    {
        $container = $this->buildContainer(['presets' => ['thumbnail' => ['width' => 200]]]);

        $expected = ['thumbnail' => ['width' => 200, 'operations' => []]];
        self::assertSame($expected, $container->getDefinition('.ux_image.presets')->getArgument(0));
    }

    public function testPresetAndOperationNamesAreKeptAsWritten(): void
    {
        $presets = ['warm-summer' => ['operations' => ['my-cdn' => ['some-op' => 1]]]];

        $container = $this->buildContainer(['presets' => $presets]);

        $argument = $container->getDefinition('.ux_image.presets')->getArgument(0);
        self::assertSame(['warm-summer'], array_keys($argument));
        self::assertSame(['my-cdn' => ['some-op' => 1]], $argument['warm-summer']['operations']);
    }

    public function testAPresetRejectsAPlacementOption(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unrecognized option "layout" under "ux_image.presets.hero".');

        $this->buildContainer(['presets' => ['hero' => ['layout' => 'full-width']]]);
    }

    public function testAPresetQualityOutOfRangeIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The value 0 is too small for path "ux_image.presets.thumbnail.quality".');

        $this->buildContainer(['presets' => ['thumbnail' => ['quality' => 0]]]);
    }

    public function testAnEmptyPresetFormatIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The path "ux_image.presets.thumbnail.format" cannot contain an empty value, but got "".');

        $this->buildContainer(['presets' => ['thumbnail' => ['format' => '']]]);
    }

    public function testANonStringPresetFormatIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid type for path "ux_image.presets.thumbnail.format". Expected "string", but got "bool".');

        $this->buildContainer(['presets' => ['thumbnail' => ['format' => true]]]);
    }

    public function testANameKeyInsideAPresetIsRejectedRatherThanRenamingIt(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unrecognized option "name" under "ux_image.presets.thumbnail".');

        $this->buildContainer(['presets' => ['thumbnail' => ['name' => 'other', 'width' => 200]]]);
    }

    public function testANullPresetOperationIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The path "ux_image.presets.blurry.operations.cloudflare.blur" cannot contain an empty value, but got null.');

        $this->buildContainer(['presets' => ['blurry' => ['operations' => ['cloudflare' => ['blur' => null]]]]]);
    }

    public function testAProviderOperationInsideAPresetIsRejectedRatherThanRenamingTheProvider(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The operation name "provider" under "ux_image.presets.hero.operations.my-cdn" is reserved.');

        $this->buildContainer(['presets' => ['hero' => ['operations' => ['my-cdn' => ['provider' => 's3', 'blur' => 5]]]]]);
    }

    public function testAPresetFitOutsideTheEnumIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "ux_image.presets.thumbnail.fit": The value "scale-down" is not allowed, expected one of "cover", "contain".');

        $this->buildContainer(['presets' => ['thumbnail' => ['fit' => 'scale-down']]]);
    }

    private function buildContainer(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.environment' => 'test',
            'kernel.build_dir' => __DIR__,
        ]));

        $bundle = new UXImageBundle();
        $bundle->getContainerExtension()->load([$config], $container);

        return $container;
    }
}
