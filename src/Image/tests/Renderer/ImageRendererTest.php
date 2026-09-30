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
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImagePresets;
use Symfony\UX\Image\Layout;
use Symfony\UX\Image\Provider\NullProvider;
use Symfony\UX\Image\Renderer\ImageRenderer;
use Symfony\UX\Image\Renderer\LayoutResolver;
use Symfony\UX\Image\Renderer\RenderOptions;
use Symfony\UX\Image\Tests\Fixtures\FakeProvider;

final class ImageRendererTest extends TestCase
{
    public function testAnAutoFormatProviderProducesNoSources(): void
    {
        $rendered = $this->renderer()->render('hero.jpg', 'Hero', new RenderOptions(width: 400, height: 300));

        self::assertSame([], $rendered->sources);
        self::assertSame('Hero', $rendered->imgAttributes['alt']);
    }

    public function testItBuildsASrcsetFromTheDerivedBreakpoints(): void
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 400));

        self::assertSame('/hero.jpg?w=400&fm=auto 400w, /hero.jpg?w=800&fm=auto 800w', $rendered->imgAttributes['srcset']);
    }

    public function testSrcsetEntriesCarryAPerBreakpointHeightWhenTheRatioIsKnown(): void
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 800, height: 450));

        self::assertSame(
            '/hero.jpg?w=800&fm=auto&h=450&fit=cover 800w, /hero.jpg?w=1600&fm=auto&h=900&fit=cover 1600w',
            $rendered->imgAttributes['srcset'],
        );
    }

    public function testFitAndQualityReachTheSrcAndEverySrcsetCandidate(): void
    {
        $options = new RenderOptions(layout: Layout::Fixed, width: 400, height: 300, fit: Fit::Contain, quality: 70);

        $rendered = $this->renderer()->render('hero.jpg', '', $options);

        self::assertSame('/hero.jpg?w=400&fm=auto&h=300&fit=contain&q=70', $rendered->imgAttributes['src']);
        self::assertSame(
            '/hero.jpg?w=400&fm=auto&h=300&fit=contain&q=70 400w, /hero.jpg?w=800&fm=auto&h=600&fit=contain&q=70 800w',
            $rendered->imgAttributes['srcset'],
        );
    }

    public function testSrcsetEntriesCarryNoHeightWhenTheRatioIsUnknown(): void
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(layout: Layout::FullWidth, height: 600));

        self::assertStringNotContainsString('&h=', $rendered->imgAttributes['srcset']);
    }

    public function testSrcAndSrcsetAgreeOnCarryingNoHeightWhenTheRatioIsUnknown(): void
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(layout: Layout::FullWidth, height: 600));

        self::assertStringNotContainsString('h=', $rendered->imgAttributes['src']);
        self::assertStringNotContainsString('&h=', $rendered->imgAttributes['srcset']);
    }

    public function testItCarriesTheLayoutSizesAndStyle(): void
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(width: 800, height: 450));

        self::assertSame('(min-width: 800px) 800px, 100vw', $rendered->imgAttributes['sizes']);
        self::assertSame('800 / 450', $rendered->imgAttributes['style']['aspect-ratio']);
    }

    public function testTheCssObjectFitMirrorsTheRequestedFitSoTheProviderCropIsNotRedoneByTheBrowser(): void
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(width: 400, height: 400, fit: Fit::Contain));

        self::assertSame('contain', $rendered->imgAttributes['style']['object-fit']);
    }

    public function testAnExplicitObjectFitStillWinsOverTheOneDerivedFromFit(): void
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(width: 400, height: 400, fit: Fit::Contain, objectFit: 'none'));

        self::assertSame('none', $rendered->imgAttributes['style']['object-fit']);
    }

    public function testWithoutAFitTheCssStillDefaultsToCover(): void
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(layout: Layout::FullWidth, height: 600));

        self::assertSame('cover', $rendered->imgAttributes['style']['object-fit']);
    }

    public function testAnImageIsOnlyLazyLoadedByDefault(): void
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(width: 800));

        self::assertSame('lazy', $rendered->imgAttributes['loading']);
        self::assertArrayNotHasKey('fetchpriority', $rendered->imgAttributes);
        self::assertArrayNotHasKey('decoding', $rendered->imgAttributes);
    }

    public function testPriorityLoadsEagerlyWithAHighFetchPriority(): void
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(width: 800, priority: true));

        self::assertSame('eager', $rendered->imgAttributes['loading']);
        self::assertSame('high', $rendered->imgAttributes['fetchpriority']);
        self::assertArrayNotHasKey('decoding', $rendered->imgAttributes);
    }

    public function testAnImgNeverHasSourcesAndFallsBackToTheLastFormatWhenTheProviderDoesNotNegotiate(): void
    {
        $renderer = new ImageRenderer(new FakeProvider(autoFormat: false), new LayoutResolver(), ['avif', 'webp', 'jpeg']);

        $rendered = $renderer->render('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 400));

        self::assertSame([], $rendered->sources);
        self::assertStringContainsString('fm=jpeg', $rendered->imgAttributes['src']);
        self::assertStringNotContainsString('fm=avif', $rendered->imgAttributes['srcset']);
    }

    public function testAPictureHasOneSourcePerConfiguredFormat(): void
    {
        $renderer = new ImageRenderer(new FakeProvider(autoFormat: false), new LayoutResolver(), ['avif', 'webp', 'jpeg']);

        $rendered = $renderer->renderPicture('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 400));

        self::assertCount(3, $rendered->sources);
        self::assertSame('image/avif', $rendered->sources[0]['type']);
        self::assertSame('image/webp', $rendered->sources[1]['type']);
        self::assertSame('image/jpeg', $rendered->sources[2]['type']);
        self::assertStringContainsString('fm=avif', $rendered->sources[0]['srcset']);
        self::assertStringContainsString('fm=jpeg', $rendered->imgAttributes['src']);
        self::assertStringContainsString('fm=jpeg', $rendered->imgAttributes['srcset']);
    }

    public function testFormatsAreIntersectedWithWhatTheProviderSupports(): void
    {
        $renderer = new ImageRenderer(new FakeProvider(autoFormat: false), new LayoutResolver(), ['avif', 'tiff', 'jpeg']);

        $rendered = $renderer->renderPicture('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 400));

        self::assertCount(2, $rendered->sources);
    }

    public function testTheSourceOrderComesFromTheConfiguredFormatsNotTheProvider(): void
    {
        $renderer = new ImageRenderer(new FakeProvider(autoFormat: false), new LayoutResolver(), ['jpeg', 'avif']);

        $rendered = $renderer->renderPicture('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 400));

        self::assertSame(['image/jpeg', 'image/avif'], array_column($rendered->sources, 'type'));
        self::assertStringContainsString('fm=avif', $rendered->imgAttributes['src']);
    }

    public function testItThrowsWhenTheConfiguredFormatsAndTheProviderShareNothing(): void
    {
        $renderer = new ImageRenderer(new FakeProvider(autoFormat: false), new LayoutResolver(), ['tiff', 'heic']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('None of the configured formats ("tiff", "heic") are supported by the "fake" provider (supported: "avif", "webp", "jpeg").');

        $renderer->render('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 400));
    }

    public function testAnExplicitFormatPinsTheOutputAndSuppressesTheAutoFormat(): void
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 400, format: 'webp'));

        self::assertSame([], $rendered->sources);
        self::assertStringContainsString('fm=webp', $rendered->imgAttributes['src']);
        self::assertStringContainsString('fm=webp', $rendered->imgAttributes['srcset']);
        self::assertStringNotContainsString('fm=auto', $rendered->imgAttributes['srcset']);
    }

    public function testAnExplicitFormatSuppressesThePictureSourcesToo(): void
    {
        $renderer = new ImageRenderer(new FakeProvider(autoFormat: false), new LayoutResolver(), ['avif', 'webp', 'jpeg']);

        $rendered = $renderer->renderPicture('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 400, format: 'jpeg'));

        self::assertSame([], $rendered->sources);
        self::assertStringContainsString('fm=jpeg', $rendered->imgAttributes['src']);
    }

    public function testItRejectsAnExplicitFormatTheProviderCannotProduce(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image format "tiff" is not supported by the "fake" provider (supported: "avif", "webp", "jpeg").');

        $this->renderer()->render('hero.jpg', '', new RenderOptions(width: 400, format: 'tiff'));
    }

    public function testAPictureHasOneSourcePerFormatEvenWhenTheProviderNegotiatesTheFormat(): void
    {
        $rendered = $this->renderer()->renderPicture('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 400));

        self::assertSame(['image/avif', 'image/webp', 'image/jpeg'], array_column($rendered->sources, 'type'));
        self::assertStringContainsString('fm=jpeg', $rendered->imgAttributes['src']);
        self::assertStringNotContainsString('fm=auto', implode(' ', array_column($rendered->sources, 'srcset')));
    }

    public function testANullProviderPictureHasNoSource(): void
    {
        $renderer = new ImageRenderer(new NullProvider(), new LayoutResolver());

        $rendered = $renderer->renderPicture('/uploads/hero.jpg', 'Hero', new RenderOptions(width: 800));

        self::assertSame([], $rendered->sources);
        self::assertSame('/uploads/hero.jpg', $rendered->imgAttributes['src']);
    }

    public function testTheNullProviderRendersTheOriginalImageWithoutASrcset(): void
    {
        $renderer = new ImageRenderer(new NullProvider(), new LayoutResolver());

        $rendered = $renderer->render('/uploads/hero.jpg', 'Hero', new RenderOptions(width: 800, height: 450, format: 'webp'));

        self::assertSame([], $rendered->sources);
        self::assertSame('/uploads/hero.jpg', $rendered->imgAttributes['src']);
        self::assertArrayNotHasKey('srcset', $rendered->imgAttributes);
        self::assertArrayNotHasKey('sizes', $rendered->imgAttributes);
        self::assertSame('800', $rendered->imgAttributes['width']);
        self::assertSame('450', $rendered->imgAttributes['height']);
        self::assertSame('800 / 450', $rendered->imgAttributes['style']['aspect-ratio']);
    }

    public function testItPassesOnlyTheActiveProviderOperations(): void
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(
            layout: Layout::Fixed,
            width: 400,
            operations: ['fake' => ['sharpen' => 3], 'cloudflare' => ['gravity' => 'auto']],
        ));

        self::assertStringContainsString('sharpen=3', $rendered->imgAttributes['src']);
        self::assertStringNotContainsString('gravity', $rendered->imgAttributes['src']);
    }

    public function testItRejectsAnUnknownOperationForTheActiveProvider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image operation "gravity" is not supported by the "fake" provider (supported: "sharpen").');

        $this->renderer()->render('hero.jpg', '', new RenderOptions(width: 400, operations: ['fake' => ['gravity' => 'auto']]));
    }

    public function testItRejectsAnOperationsBlockThatIsNotAMap(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "operations.fake" option must be a map of operation names to values, "string" given.');

        $this->renderer()->render('hero.jpg', '', new RenderOptions(width: 400, operations: ['fake' => 'invalid']));
    }

    public function testItRejectsAFixedLayoutWithoutAWidth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "fixed" layout requires a width.');

        new RenderOptions(layout: Layout::Fixed);
    }

    public function testItRejectsAConstrainedLayoutWithoutAWidth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "constrained" layout requires a width.');

        new RenderOptions(layout: Layout::Constrained);
    }

    public function testItRejectsAFullWidthLayoutWithoutAHeight(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "full-width" layout requires a height.');

        new RenderOptions(layout: Layout::FullWidth);
    }

    public function testFullWidthLayoutDoesNotRequireAWidth(): void
    {
        $options = new RenderOptions(layout: Layout::FullWidth, height: 600);

        self::assertNull($options->width);
    }

    public function testFullWidthRendersWithoutAWidth(): void
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(layout: Layout::FullWidth, height: 600));

        self::assertNotSame('', $rendered->imgAttributes['srcset']);
        self::assertStringContainsString('6016w', $rendered->imgAttributes['srcset']);
        self::assertSame('100vw', $rendered->imgAttributes['sizes']);
        self::assertArrayNotHasKey('width', $rendered->imgAttributes);
        self::assertSame('600px', $rendered->imgAttributes['style']['height']);
    }

    public function testFullWidthSrcDoesNotFallBackToTheTopOfTheResolutionLadder(): void
    {
        $rendered = $this->renderer()->render('hero.jpg', '', new RenderOptions(layout: Layout::FullWidth, height: 600, format: 'webp'));

        self::assertStringNotContainsString('w=', $rendered->imgAttributes['src']);
        self::assertSame('/hero.jpg?fm=webp', $rendered->imgAttributes['src']);
    }

    public function testFixedAndConstrainedSrcKeepTheirOwnWidthRegardlessOfTheFullWidthFallback(): void
    {
        $fixed = $this->renderer()->render('hero.jpg', '', new RenderOptions(layout: Layout::Fixed, width: 400));
        $constrained = $this->renderer()->render('hero.jpg', '', new RenderOptions(layout: Layout::Constrained, width: 400));

        self::assertStringContainsString('w=400', $fixed->imgAttributes['src']);
        self::assertStringContainsString('w=400', $constrained->imgAttributes['src']);
    }

    public function testAPresetFillsTheRenderOptions(): void
    {
        $rendered = $this->presetRenderer()->render('hero.jpg', 'Hero', new RenderOptions(preset: 'thumbnail'));

        self::assertSame('200', $rendered->imgAttributes['width']);
        self::assertSame('200', $rendered->imgAttributes['height']);
        self::assertSame('/hero.jpg?w=200&fm=auto&h=200&fit=cover&q=70&sharpen=2', $rendered->imgAttributes['src']);
    }

    public function testAnExplicitOptionWinsOverThePreset(): void
    {
        $rendered = $this->presetRenderer()->render('hero.jpg', 'Hero', new RenderOptions(width: 300, operations: ['fake' => ['sharpen' => 5]], preset: 'thumbnail'));

        self::assertSame('/hero.jpg?w=300&fm=auto&h=200&fit=cover&q=70&sharpen=5', $rendered->imgAttributes['src']);
    }

    public function testThePresetFitWinsOverTheCoverDefaultOfBothDimensions(): void
    {
        $rendered = $this->presetRenderer()->render('hero.jpg', 'Hero', new RenderOptions(width: 400, height: 400, preset: 'boxed'));

        self::assertStringContainsString('fit=contain', $rendered->imgAttributes['src']);
    }

    public function testAPresetIsAppliedWithTheNullProvider(): void
    {
        $renderer = new ImageRenderer(new NullProvider(), new LayoutResolver(), presets: new ImagePresets(['thumbnail' => ['width' => 200]]));

        $rendered = $renderer->render('/uploads/hero.jpg', 'Hero', new RenderOptions(preset: 'thumbnail'));

        self::assertSame('/uploads/hero.jpg', $rendered->imgAttributes['src']);
        self::assertSame('200', $rendered->imgAttributes['width']);
    }

    public function testAnUnknownPresetIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image preset "thumbnial" does not exist (defined: "thumbnail", "boxed").');

        $this->presetRenderer()->render('hero.jpg', 'Hero', new RenderOptions(preset: 'thumbnial'));
    }

    private function renderer(): ImageRenderer
    {
        return new ImageRenderer(new FakeProvider(), new LayoutResolver(), ['avif', 'webp', 'jpeg']);
    }

    private function presetRenderer(): ImageRenderer
    {
        $presets = new ImagePresets([
            'thumbnail' => ['width' => 200, 'height' => 200, 'quality' => 70, 'operations' => ['fake' => ['sharpen' => 2]]],
            'boxed' => ['fit' => 'contain'],
        ]);

        return new ImageRenderer(new FakeProvider(), new LayoutResolver(), ['avif', 'webp', 'jpeg'], presets: $presets);
    }
}
