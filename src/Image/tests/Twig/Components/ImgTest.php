<?php

namespace Symfony\UX\Image\Tests\Twig\Components;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Image\Provider\ProviderInterface;
use Symfony\UX\Image\Provider\ProviderRegistry;
use Symfony\UX\Image\Service\PreloadManager;
use Symfony\UX\Image\Tests\TestHelper\HtmlTestHelper;
use Symfony\UX\Image\Twig\Components\Img;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

class ImgTest extends KernelTestCase
{
    use HtmlTestHelper;
    use InteractsWithTwigComponents;

    /** @var ProviderInterface&MockObject */
    private ProviderInterface $provider;

    /** @var ProviderInterface&MockObject */
    private ProviderInterface $customProvider;

    private PreloadManager $preloadManager;

    private ProviderRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $container = static::getContainer();
        $this->registry = $container->get(ProviderRegistry::class);
        $this->preloadManager = $container->get(PreloadManager::class);
        $this->preloadManager->reset();

        // Setup default mock provider
        $this->provider = $this->createMock(ProviderInterface::class);
        $this->provider->method('getName')->willReturn('mock');
        $this->provider
            ->method('getImage')
            ->willReturnCallback(function ($src, $modifiers) {
                return $src . '?' . http_build_query($modifiers);
            });

        $this->registry->addProvider($this->provider);
        $this->registry->setDefaultProvider('mock');
    }

    public function testComponentMount(): void
    {
        $component = $this->mountTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
            ]
        );

        $this->assertInstanceOf(Img::class, $component);
        $this->assertSame('/image.jpg', $component->src);
    }

    public function testEmptySrcThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Image src cannot be empty');

        $this->mountTwigComponent(
            name: 'img',
            data: [
                'src' => '',
            ]
        );
    }

    public function testComponentRenders(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'alt' => 'Test image',
                'class' => 'img-fluid rounded',
                'referrerpolicy' => 'origin',
                'id' => 'image',
                'data-controller' => 'responsive-image',
                'width' => 100,
                'height' => 100,
                'loading' => 'lazy',
                'fetchpriority' => 'auto',
                'fallback' => 'auto',
                'format' => 'webp',
                'quality' => '80',
                'fit' => 'cover',
                'focal' => 'center',
            ]
        );

        // Test standard HTML attributes
        $this->assertImageAttribute($rendered, 'alt', 'Test image');
        $this->assertImageAttribute($rendered, 'class', 'img-fluid rounded');
        $this->assertImageAttribute($rendered, 'referrerpolicy', 'origin');
        $this->assertImageAttribute($rendered, 'id', 'image');
        $this->assertImageAttribute($rendered, 'data-controller', 'responsive-image');
        $this->assertImageAttribute($rendered, 'width', '100');
        $this->assertImageAttribute($rendered, 'height', '100');
        $this->assertImageAttribute($rendered, 'loading', 'lazy');
        $this->assertImageAttribute($rendered, 'fetchpriority', 'auto');
    }

    public function testPresetConfiguration(): void
    {
        $component = $this->mountTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'preset' => 'hero',
            ]
        );

        $this->assertStringContainsString('high', $component->fetchpriority);
        $this->assertStringContainsString('16:9', $component->ratio);
        $this->assertStringContainsString('100vw sm:50vw md:400px', $component->width);
        $this->assertTrue($component->preload);
    }

    public function testPlaceholderRendering(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'placeholder' => 'blur',
                'placeholder-class' => 'custom-placeholder',
            ]
        );

        $this->assertStringContainsString('class="custom-placeholder"', $rendered);
    }

    public function testFixedWidth(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '100',
            ]
        );

        $this->assertImageSrcParam($rendered, 'width', '100');
    }

    public function testFixedWidthPx(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '100px',
            ]
        );

        $this->assertImageSrcParam($rendered, 'width', '100');
        $this->assertStringNotContainsString('sizes="', $rendered);
        $this->assertStringNotContainsString('srcset="', $rendered);
    }

    public function testFixedWidthLarge(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '1000',
            ]
        );

        $this->assertImageSrcParam($rendered, 'width', '1000');
        $this->assertStringNotContainsString('sizes="', $rendered);
        $this->assertStringNotContainsString('srcset="', $rendered);
    }

    public function testFixedWidthBreakpoints(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => 'sm:50 md:100 lg:200',
            ]
        );

        $attributes = $this->parseImageAttributes($rendered);
        $this->assertImageSrcParam($rendered, 'width', '50');
        $this->assertStringContainsString('/image.jpg?width=50 50w', $attributes['srcset']);
        $this->assertStringContainsString('/image.jpg?width=100 100w', $attributes['srcset']);
        $this->assertStringContainsString('/image.jpg?width=200 200w', $attributes['srcset']);
        $this->assertStringContainsString('(max-width: 640px) 50px', $attributes['sizes']);
        $this->assertStringContainsString('(max-width: 768px) 100px', $attributes['sizes']);
        $this->assertStringContainsString('(max-width: 1024px) 200px', $attributes['sizes']);
    }

    public function testFullscreen(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '100vw',
            ]
        );

        $attributes = $this->parseImageAttributes($rendered);
        $this->assertImageSrcParam($rendered, 'width', '640');

        // Test srcset values
        $this->assertStringContainsString('/image.jpg?width=640 640w', $attributes['srcset']);
        $this->assertStringContainsString('/image.jpg?width=768 768w', $attributes['srcset']);
        $this->assertStringContainsString('/image.jpg?width=1024 1024w', $attributes['srcset']);
        $this->assertStringContainsString('/image.jpg?width=1280 1280w', $attributes['srcset']);
        $this->assertStringContainsString('/image.jpg?width=1536 1536w', $attributes['srcset']);

        // Test sizes attribute
        $this->assertStringContainsString('100vw', $attributes['sizes']);
    }

    public function testHalfscreenAndFixed(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '50vw lg:400px',
            ]
        );

        $this->assertImageSrcParam($rendered, 'width', '320');
        $this->assertStringContainsString('/image.jpg?width=320 320w', $rendered);
        $this->assertStringContainsString('/image.jpg?width=400 400w', $rendered);
        $this->assertStringContainsString('(max-width: 1024px) 50vw', $rendered);
        $this->assertStringContainsString('400px', $rendered);
    }

    public function testMixedValues(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '400 sm:500 md:100vw',
            ]
        );

        $this->assertImageSrcParam($rendered, 'width', '400');
        $this->assertStringContainsString('/image.jpg?width=400 400w', $rendered);
        $this->assertStringContainsString('/image.jpg?width=500 500w', $rendered);
        $this->assertStringContainsString('/image.jpg?width=768 768w', $rendered);
        $this->assertStringContainsString('/image.jpg?width=1024 1024w', $rendered);
        $this->assertStringContainsString('/image.jpg?width=1280 1280w', $rendered);
        $this->assertStringContainsString('/image.jpg?width=1536 1536w', $rendered);
        $this->assertStringContainsString('(max-width: 640px) 400px', $rendered);
        $this->assertStringContainsString('(max-width: 768px) 500px', $rendered);
        $this->assertStringContainsString('100vw', $rendered);
    }

    public function testDensities(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => 100,
                'densities' => 'x1 x2',
            ]
        );

        $this->assertImageSrcParam($rendered, 'width', '100');
        $this->assertStringContainsString('/image.jpg?width=100 100w', $rendered);
        $this->assertStringContainsString('/image.jpg?width=200 200w', $rendered);
    }

    public function testPreloadSimpleImage(): void
    {
        // First verify the PreloadManager service exists
        $preloadManager = static::getContainer()->get(PreloadManager::class);
        $this->assertInstanceOf(PreloadManager::class, $preloadManager);

        // Reset the PreloadManager
        $preloadManager->reset();

        // Mount component with preload=true
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '400',
                'preload' => true,
            ]
        );

        $this->assertImageSrcParam($rendered, 'width', '400');

        $preloadTags = $preloadManager->getPreloadTags();

        $this->assertStringContainsString(
            '<link rel="preload" as="image" href="/image.jpg?width=400',
            $preloadTags,
            'Preload tags should contain the expected link tag'
        );
    }

    public function testPreloadResponsiveImage(): void
    {
        $this->mountTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '100vw',
                'preload' => true,
            ]
        );

        $preloadManager = static::getContainer()->get(PreloadManager::class);
        $preloadTags = $preloadManager->getPreloadTags();

        $this->assertStringContainsString('imagesrcset="', $preloadTags);
        $this->assertStringContainsString('imagesizes="100vw"', $preloadTags);
        $this->assertStringContainsString('/image.jpg?width=640 640w', $preloadTags);
        $this->assertStringContainsString('/image.jpg?width=1536 1536w', $preloadTags);
    }

    public function testPreloadDisabledByDefault(): void
    {
        $this->mountTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '400',
            ]
        );

        $preloadManager = static::getContainer()->get(PreloadManager::class);
        $preloadTags = $preloadManager->getPreloadTags();

        $this->assertEmpty($preloadTags);
    }

    public function testFormatParameter(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '400',
                'format' => 'avif',
            ]
        );

        $this->assertImageSrcParam($rendered, 'width', '400');
        $this->assertImageSrcParam($rendered, 'format', 'avif');
    }

    public function testQualityParameter(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '400',
                'quality' => '90',
            ]
        );

        $this->assertImageSrcParam($rendered, 'width', '400');
        $this->assertImageSrcParam($rendered, 'quality', '90');
    }

    public function testFitParameter(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '400',
                'fit' => 'contain',
            ]
        );

        $this->assertImageSrcParam($rendered, 'fit', 'contain');
    }

    public function testBackgroundParameter(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '400',
                'background' => '#ffffff',
            ]
        );

        $this->assertImageSrcParam($rendered, 'width', '400');
        $this->assertImageSrcParam($rendered, 'background', '#ffffff');
    }

    public function testFocalParameter(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '400',
                'focal' => 'top',
            ]
        );

        $this->assertImageSrcParam($rendered, 'width', '400');
        $this->assertImageSrcParam($rendered, 'focal', 'top');
    }

    public function testFallbackParameter(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '100w',
                'format' => 'webp',
                'fallback' => 'jpg',
            ]
        );

        $this->assertImageSrcParam($rendered, 'width', '100');
        $this->assertImageSrcParam($rendered, 'format', 'jpg');
    }

    public function testDefaultFallbackParameter(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.webp',
                'width' => '100w',
                'format' => 'webp',
                'fallback' => 'auto',
            ]
        );

        $this->assertImageSrcParam($rendered, 'width', '100');
        $this->assertImageSrcParam($rendered, 'format', 'png');
    }

    public function testEmptyFallbackParameter(): void
    {
        $component = $this->mountTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '400',
                'format' => 'webp',
                'fallback-format' => 'empty',
            ]
        );

        $this->assertEquals(Img::EMPTY_GIF, $component->getSrcComputed());
    }

    public function testRatioParameter(): void
    {
        // Mount the component to test the computed values
        $component = $this->mountTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '400',
                'ratio' => '16:9',
            ]
        );

        // Test the computed values directly
        $this->assertEquals('400', $component->width);
        $this->assertEquals('16:9', $component->ratio);

        // Test the rendered HTML
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '400',
                'ratio' => '16:9',
            ]
        );

        // Test using our helper methods
        $this->assertImageSrcParam($rendered, 'width', '400');
        $this->assertImageSrcParam($rendered, 'ratio', '16:9');
    }

    public function testCustomProvider(): void
    {
        $this->customProvider = $this->createMock(ProviderInterface::class);
        $this->customProvider->method('getName')->willReturn('custom');
        $this->customProvider
            ->method('getImage')
            ->willReturnCallback(function ($src, $modifiers) {
                return 'custom://' . $src . '?' . http_build_query($modifiers);
            });

        $this->registry->addProvider($this->customProvider);

        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => 'image.jpg',
                'width' => '400',
                'provider' => 'custom',
            ]
        );

        $this->assertStringContainsString('custom://image.jpg?width=400', $rendered);
    }

    public function testResponsiveWidthNotOutputAsHtmlAttribute(): void
    {
        // Test that responsive width syntax is NOT output as HTML width attribute
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '100vw',
                'alt' => 'Test',
            ]
        );

        $attributes = $this->parseImageAttributes($rendered);

        // Should NOT have width attribute (invalid HTML)
        $this->assertArrayNotHasKey('width', $attributes, 'Responsive width "100vw" should not be output as HTML width attribute');

        // Should have sizes attribute with the responsive value
        $this->assertArrayHasKey('sizes', $attributes);
        $this->assertStringContainsString('100vw', $attributes['sizes']);
    }

    public function testNumericWidthOutputAsHtmlAttribute(): void
    {
        // Test that numeric width IS output as HTML width attribute
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '800',
                'height' => 600,
                'alt' => 'Test',
            ]
        );

        $attributes = $this->parseImageAttributes($rendered);

        // Should have valid HTML width and height attributes
        $this->assertImageAttribute($rendered, 'width', '800');
        $this->assertImageAttribute($rendered, 'height', '600');
    }

    public function testBreakpointWidthNotOutputAsHtmlAttribute(): void
    {
        // Test that breakpoint syntax is NOT output as HTML width attribute
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => 'sm:50vw md:800px',
                'alt' => 'Test',
            ]
        );

        $attributes = $this->parseImageAttributes($rendered);

        // Should NOT have width attribute (invalid HTML)
        $this->assertArrayNotHasKey('width', $attributes, 'Breakpoint width syntax should not be output as HTML width attribute');

        // Should have sizes attribute with the responsive value
        $this->assertArrayHasKey('sizes', $attributes);
    }

    public function testEmptyLoadingAttributeNotRendered(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '100',
                'loading' => '',
            ]
        );

        // Empty loading attribute should not be rendered (W3C compliant)
        $this->assertStringNotContainsString('loading=""', $rendered);
        $this->assertStringNotContainsString('loading= ', $rendered);
    }

    public function testValidLoadingAttributeRendered(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'img',
            data: [
                'src' => '/image.jpg',
                'width' => '100',
                'loading' => 'lazy',
            ]
        );

        // Valid loading attribute should be rendered
        $this->assertStringContainsString('loading="lazy"', $rendered);
    }
}
