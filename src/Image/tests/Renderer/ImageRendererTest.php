<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Renderer;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Exception\LogicException;
use Symfony\UX\Image\Layout;
use Symfony\UX\Image\Provider\NullProvider;
use Symfony\UX\Image\Renderer\ImageRenderer;
use Symfony\UX\Image\Renderer\LayoutResolver;
use Symfony\UX\Image\Renderer\RenderOptions;
use Symfony\UX\Image\Tests\Fixtures\FakeProvider;

final class ImageRendererTest extends TestCase
{
    public function testAnAutoFormatProviderProducesNoSources()
    {
        $rendered = $this->renderer()->render('hero.jpg', 'Hero', new RenderOptions(width: 400, height: 300));

        self::assertSame([], $rendered->sources);
        self::assertSame('Hero', $rendered->imgAttributes['alt']);
    }

    public function testItBuildsASrcsetFromTheDerivedBreakpoints()
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 400));

        self::assertSame('/hero.jpg?w=400&fm=auto 400w, /hero.jpg?w=800&fm=auto 800w', $rendered->imgAttributes['srcset']);
    }

    public function testSrcsetEntriesCarryAPerBreakpointHeightWhenTheRatioIsKnown()
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 800, height: 450));

        self::assertSame(
            '/hero.jpg?w=800&fm=auto&h=450 800w, /hero.jpg?w=1600&fm=auto&h=900 1600w',
            $rendered->imgAttributes['srcset'],
        );
    }

    public function testSrcsetEntriesCarryNoHeightWhenTheRatioIsUnknown()
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(layout: Layout::FullWidth, height: 600));

        self::assertStringNotContainsString('&h=', $rendered->imgAttributes['srcset']);
    }

    public function testSrcAndSrcsetAgreeOnCarryingNoHeightWhenTheRatioIsUnknown()
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(layout: Layout::FullWidth, height: 600));

        self::assertStringNotContainsString('h=', $rendered->imgAttributes['src']);
        self::assertStringNotContainsString('&h=', $rendered->imgAttributes['srcset']);
    }

    public function testItCarriesTheLayoutSizesAndStyle()
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(width: 800, height: 450));

        self::assertSame('(min-width: 800px) 800px, 100vw', $rendered->imgAttributes['sizes']);
        self::assertStringContainsString('aspect-ratio: 800 / 450', $rendered->imgAttributes['style']->getValue());
    }

    public function testPriorityFlipsTheLoadingHints()
    {
        $lazy = $this->renderer()->render('hero.jpg', '', new RenderOptions(width: 800));
        $eager = $this->renderer()->render('hero.jpg', '', new RenderOptions(width: 800, priority: true));

        self::assertSame('lazy', $lazy->imgAttributes['loading']);
        self::assertSame('auto', $lazy->imgAttributes['fetchpriority']);
        self::assertSame('eager', $eager->imgAttributes['loading']);
        self::assertSame('high', $eager->imgAttributes['fetchpriority']);
    }

    public function testAProviderWithoutAutoFormatProducesOneSourcePerFormat()
    {
        $renderer = new ImageRenderer(new FakeProvider(autoFormat: false), new LayoutResolver(), ['avif', 'webp', 'jpeg']);

        $rendered = $renderer->render('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 400));

        self::assertCount(3, $rendered->sources);
        self::assertSame('image/avif', $rendered->sources[0]['type']);
        self::assertSame('image/webp', $rendered->sources[1]['type']);
        self::assertSame('image/jpeg', $rendered->sources[2]['type']);
        self::assertStringContainsString('fm=avif', $rendered->sources[0]['srcset']);
        self::assertStringContainsString('fm=jpeg', $rendered->imgAttributes['src']);
        self::assertStringContainsString('fm=jpeg', $rendered->imgAttributes['srcset']);
    }

    public function testFormatsAreIntersectedWithWhatTheProviderSupports()
    {
        $renderer = new ImageRenderer(new FakeProvider(autoFormat: false), new LayoutResolver(), ['avif', 'tiff', 'jpeg']);

        $rendered = $renderer->render('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 400));

        self::assertCount(2, $rendered->sources);
    }

    public function testTheSourceOrderComesFromTheConfiguredFormatsNotTheProvider()
    {
        $renderer = new ImageRenderer(new FakeProvider(autoFormat: false), new LayoutResolver(), ['jpeg', 'avif']);

        $rendered = $renderer->render('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 400));

        self::assertSame(['image/jpeg', 'image/avif'], array_column($rendered->sources, 'type'));
        self::assertStringContainsString('fm=avif', $rendered->imgAttributes['src']);
    }

    public function testItThrowsWhenTheConfiguredFormatsAndTheProviderShareNothing()
    {
        $renderer = new ImageRenderer(new FakeProvider(autoFormat: false), new LayoutResolver(), ['tiff', 'heic']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('None of the configured formats ("tiff", "heic") are supported by the "fake" provider (supported: "avif", "webp", "jpeg").');

        $renderer->render('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 400));
    }

    public function testAnExplicitFormatPinsTheOutputAndSuppressesTheAutoFormat()
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 400, format: 'webp'));

        self::assertSame([], $rendered->sources);
        self::assertStringContainsString('fm=webp', $rendered->imgAttributes['src']);
        self::assertStringContainsString('fm=webp', $rendered->imgAttributes['srcset']);
        self::assertStringNotContainsString('fm=auto', $rendered->imgAttributes['srcset']);
    }

    public function testAnExplicitFormatSuppressesThePictureSourcesToo()
    {
        $renderer = new ImageRenderer(new FakeProvider(autoFormat: false), new LayoutResolver(), ['avif', 'webp', 'jpeg']);

        $rendered = $renderer->render('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 400, format: 'jpeg'));

        self::assertSame([], $rendered->sources);
        self::assertStringContainsString('fm=jpeg', $rendered->imgAttributes['src']);
    }

    public function testItRejectsAnExplicitFormatTheProviderCannotProduce()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image format "tiff" is not supported by the "fake" provider (supported: "avif", "webp", "jpeg").');

        $this->renderer()->render('hero.jpg', '', new RenderOptions(width: 400, format: 'tiff'));
    }

    public function testAPinnedFormatOnAnUnconfiguredProviderFailsWithTheInstallABridgeMessageNotAFormatMismatch()
    {
        $renderer = new ImageRenderer(new NullProvider(), new LayoutResolver());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No image provider is configured. Install a bridge such as "symfony/ux-glide-image", "symfony/ux-keycdn-image" or "symfony/ux-cloudflare-image".');

        $renderer->render('hero.jpg', '', new RenderOptions(width: 400, format: 'webp'));
    }

    public function testItPassesOnlyTheActiveProviderOperations()
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(
            layout: Layout::Fixed,
            width: 400,
            operations: ['fake' => ['sharpen' => 3], 'cloudflare' => ['gravity' => 'auto']],
        ));

        self::assertStringContainsString('sharpen=3', $rendered->imgAttributes['src']);
        self::assertStringNotContainsString('gravity', $rendered->imgAttributes['src']);
    }

    public function testItRejectsAnUnknownOperationForTheActiveProvider()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image operation "gravity" is not supported by the "fake" provider (supported: "sharpen").');

        $this->renderer()->render('hero.jpg', '', new RenderOptions(width: 400, operations: ['fake' => ['gravity' => 'auto']]));
    }

    public function testItRejectsAFixedLayoutWithoutAWidth()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "fixed" layout requires a width.');

        new RenderOptions(layout: Layout::Fixed);
    }

    public function testItRejectsAConstrainedLayoutWithoutAWidth()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "constrained" layout requires a width.');

        new RenderOptions(layout: Layout::Constrained);
    }

    public function testItRejectsAFullWidthLayoutWithoutAHeight()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "full-width" layout requires a height.');

        new RenderOptions(layout: Layout::FullWidth);
    }

    public function testFullWidthLayoutDoesNotRequireAWidth()
    {
        $options = new RenderOptions(layout: Layout::FullWidth, height: 600);

        self::assertNull($options->width);
    }

    public function testFullWidthRendersWithoutAWidth()
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(layout: Layout::FullWidth, height: 600));

        self::assertNotSame('', $rendered->imgAttributes['srcset']);
        self::assertStringContainsString('6016w', $rendered->imgAttributes['srcset']);
        self::assertSame('100vw', $rendered->imgAttributes['sizes']);
        self::assertArrayNotHasKey('width', $rendered->imgAttributes);
        self::assertStringContainsString('height: 600px', $rendered->imgAttributes['style']->getValue());
    }

    public function testFullWidthSrcDoesNotFallBackToTheTopOfTheResolutionLadder()
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(layout: Layout::FullWidth, height: 600, format: 'webp'));

        self::assertStringNotContainsString('w=', $rendered->imgAttributes['src']);
        self::assertSame('/hero.jpg?fm=webp', $rendered->imgAttributes['src']);
    }

    public function testFixedAndConstrainedSrcKeepTheirOwnWidthRegardlessOfTheFullWidthFallback()
    {
        $fixed = $this->renderer()->render('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 400));
        $constrained = $this->renderer()->render('hero.jpg', '', new RenderOptions(layout: Layout::Constrained, width: 400));

        self::assertStringContainsString('w=400', $fixed->imgAttributes['src']);
        self::assertStringContainsString('w=400', $constrained->imgAttributes['src']);
    }

    private function renderer(): ImageRenderer
    {
        return new ImageRenderer(new FakeProvider(), new LayoutResolver(), ['avif', 'webp', 'jpeg']);
    }
}
