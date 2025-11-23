<?php

namespace Symfony\UX\Image\Tests\Twig\Components;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Image\Provider\ProviderInterface;
use Symfony\UX\Image\Provider\ProviderRegistry;
use Symfony\UX\Image\Tests\TestHelper\HtmlTestHelper;
use Symfony\UX\Image\Twig\Components\Picture;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

class PictureTest extends KernelTestCase
{
    use HtmlTestHelper;
    use InteractsWithTwigComponents;

    /** @var ProviderInterface&MockObject */
    private ProviderInterface $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $registry = static::getContainer()->get(ProviderRegistry::class);

        $this->provider = $this->createMock(ProviderInterface::class);
        $this->provider->method('getName')->willReturn('mock');
        $this->provider
            ->method('getImage')
            ->willReturnCallback(function ($src, $modifiers) {
                return $src . '?' . http_build_query($modifiers);
            });

        $registry->addProvider($this->provider);
        $registry->setDefaultProvider('mock');
    }

    public function testComponentMount(): void
    {
        $component = $this->mountTwigComponent(
            name: 'picture',
            data: [
                'src' => '/image.jpg',
            ]
        );

        $this->assertInstanceOf(Picture::class, $component);
        $this->assertSame('/image.jpg', $component->src);
    }

    public function testEmptySrcThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Image src cannot be empty');

        $this->mountTwigComponent(
            name: 'picture',
            data: [
                'src' => '',
            ]
        );
    }

    public function testComponentRenders(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'picture',
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
                'sizes' => '(max-width: 768px) 100vw, 50vw',
                'fallback' => 'auto',
                'class' => 'img-fluid rounded',
            ]
        );

        $this->assertStringContainsString('alt="Test image"', $rendered);
        $this->assertStringContainsString('class="img-fluid rounded"', $rendered);
        $this->assertStringContainsString('referrerpolicy="origin"', $rendered);
        $this->assertStringContainsString('id="image"', $rendered);
        $this->assertStringContainsString('data-controller="responsive-image"', $rendered);
        $this->assertStringContainsString('width="100"', $rendered);
        $this->assertStringContainsString('height="100"', $rendered);
    }

    public function testFixedWidth(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'picture',
            data: [
                'src' => '/image.jpg',
                'width' => '100',
            ]
        );

        $this->assertImageSrcParam($rendered, 'width', '100');
        $this->assertStringContainsString('width="100"', $rendered);
    }

    public function testResponsiveWidth(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'picture',
            data: [
                'src' => '/image.jpg',
                'width' => 'sm:50 md:100 lg:200',
            ]
        );

        $this->assertImageSrcParam($rendered, 'width', '50');
        $this->assertSourceAttribute($rendered, 'media', '(max-width: 640px)', 0);
        $this->assertSourceAttribute($rendered, 'media', '(max-width: 768px)', 1);
        $this->assertSourceAttribute($rendered, 'media', '(max-width: 1024px)', 2);
        $this->assertSourceAttribute($rendered, 'srcset', '/image.jpg?width=50', 0);
        $this->assertSourceAttribute($rendered, 'srcset', '/image.jpg?width=100', 1);
        $this->assertSourceAttribute($rendered, 'srcset', '/image.jpg?width=200', 2);
    }

    public function testViewportWidthUnit(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'picture',
            data: [
                'src' => '/image.jpg',
                'width' => '100vw',
            ]
        );

        $this->assertImageSrcParam($rendered, 'width', '640');
        $this->assertStringContainsString('srcset="/image.jpg?width=640 640w"', $rendered);
        $this->assertStringContainsString('srcset="/image.jpg?width=768 768w"', $rendered);
        $this->assertStringContainsString('srcset="/image.jpg?width=1024 1024w"', $rendered);
        $this->assertStringContainsString('srcset="/image.jpg?width=1280 1280w"', $rendered);
        $this->assertStringContainsString('srcset="/image.jpg?width=1536 1536w"', $rendered);
        $this->assertStringContainsString('sizes="100vw"', $rendered);
    }

    public function testEmptyLoadingAttributeNotRendered(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'picture',
            data: [
                'src' => '/image.jpg',
                'width' => '100',
                'loading' => '',
            ]
        );

        // Empty loading attribute should not be rendered
        $this->assertStringNotContainsString('loading=""', $rendered);
        $this->assertStringNotContainsString('loading= ', $rendered);
    }

    public function testValidLoadingAttributeRendered(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'picture',
            data: [
                'src' => '/image.jpg',
                'width' => '100',
                'loading' => 'lazy',
            ]
        );

        // Valid loading attribute should be rendered
        $this->assertStringContainsString('loading="lazy"', $rendered);
    }

    public function testArtDirectionWithBreakpointSpecificRatios(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'picture',
            data: [
                'src' => '/image.jpg',
                'width' => '100vw md:80vw',
                'ratio' => 'sm:1:1 md:16:9',
            ]
        );

        // Check that the first breakpoint (sm) uses 1:1 ratio (square images)
        // For 640px breakpoint with 1:1 ratio: height should equal width (640x640)
        $this->assertStringContainsString('ratio=1%3A1', $rendered);

        // Check that larger breakpoints (md+) use 16:9 ratio
        // For 768px breakpoint with 16:9 ratio: height should be width*9/16 (768x432)
        $this->assertStringContainsString('ratio=16%3A9', $rendered);

        // Verify exclusive media queries are generated for art direction
        $this->assertStringContainsString('media="(min-width: 640px) and (max-width: 767px)"', $rendered);
        $this->assertStringContainsString('media="(min-width: 768px) and (max-width: 1023px)"', $rendered);
    }

    public function testPictureWithoutArtDirectionUsesSimpleMediaQueries(): void
    {
        // When all breakpoints use the same ratio, we don't need exclusive ranges
        $rendered = $this->renderTwigComponent(
            name: 'picture',
            data: [
                'src' => '/image.jpg',
                'width' => '100vw',
                'ratio' => '16:9',
            ]
        );

        // Should use simple min-width queries without max-width
        $this->assertStringContainsString('media="(min-width: 640px)"', $rendered);
        $this->assertStringNotContainsString('max-width', $rendered);
    }

    public function testPictureWithSingleBreakpointRatio(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'picture',
            data: [
                'src' => '/image.jpg',
                'width' => '100vw',
                'ratio' => 'md:16:9',
            ]
        );

        // md and larger should use 16:9
        $this->assertStringContainsString('ratio=16%3A9', $rendered);

        // sm should not have a ratio modifier (no ratio specified for it)
        $this->assertStringNotContainsString('/image.jpg?width=640&amp;ratio=', $rendered);
    }

    public function testRatioCascadesAcrossBreakpoints(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'picture',
            data: [
                'src' => '/image.jpg',
                'width' => '100vw',
                'ratio' => 'sm:1:1 lg:16:9',
            ]
        );

        // sm and md should use 1:1 (sm cascades to md)
        $this->assertStringContainsString('width=640&amp;ratio=1%3A1', $rendered);
        $this->assertStringContainsString('width=768&amp;ratio=1%3A1', $rendered);

        // lg and larger should use 16:9 (lg cascades to xl, 2xl)
        $this->assertStringContainsString('width=1024&amp;ratio=16%3A9', $rendered);
        $this->assertStringContainsString('width=1280&amp;ratio=16%3A9', $rendered);
        $this->assertStringContainsString('width=1536&amp;ratio=16%3A9', $rendered);
    }
}
