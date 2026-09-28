<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImagePresets;
use Symfony\UX\Image\ImageUrlGenerator;
use Symfony\UX\Image\Provider\NullProvider;
use Symfony\UX\Image\Tests\Fixtures\FakeProvider;

final class ImageUrlGeneratorTest extends TestCase
{
    private const array PRESETS = [
        'thumbnail' => ['width' => 200, 'height' => 200, 'quality' => 70, 'operations' => ['fake' => ['sharpen' => 2]]],
        'wide' => ['width' => 1200],
        'boxed' => ['width' => 400, 'height' => 400, 'fit' => 'contain'],
    ];

    public function testItGeneratesOneUrlThroughTheProvider(): void
    {
        $url = new ImageUrlGenerator(new FakeProvider())->generate('og.jpg', width: 1200, height: 630, fit: Fit::Cover, format: 'jpeg', quality: 80);

        self::assertSame('/og.jpg?w=1200&fm=jpeg&h=630&fit=cover&q=80', $url);
    }

    public function testOnlyTheActiveProviderOperationsAreApplied(): void
    {
        $operations = ['fake' => ['sharpen' => 3], 'cloudflare' => ['gravity' => 'auto']];

        $url = new ImageUrlGenerator(new FakeProvider())->generate('og.jpg', width: 1200, operations: $operations);

        self::assertSame('/og.jpg?w=1200&fm=&sharpen=3', $url);
    }

    public function testItRejectsAFormatTheProviderCannotProduce(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image format "tiff" is not supported by the "fake" provider (supported: "avif", "webp", "jpeg").');

        new ImageUrlGenerator(new FakeProvider())->generate('og.jpg', format: 'tiff');
    }

    public function testTheAutoFormatNeedsAProviderThatNegotiatesIt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image format "auto" is not supported by the "fake" provider (supported: "avif", "webp", "jpeg").');

        new ImageUrlGenerator(new FakeProvider(autoFormat: false))->generate('og.jpg', format: 'auto');
    }

    public function testTheAutoFormatIsAcceptedWhenTheProviderNegotiatesIt(): void
    {
        self::assertSame('/og.jpg?fm=auto', new ImageUrlGenerator(new FakeProvider())->generate('og.jpg', format: 'auto'));
    }

    public function testItRejectsAnOperationTheProviderDoesNotSupport(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image operation "gravity" is not supported by the "fake" provider (supported: "sharpen").');

        new ImageUrlGenerator(new FakeProvider())->generate('og.jpg', operations: ['fake' => ['gravity' => 'auto']]);
    }

    public function testItRejectsAnOperationsBlockThatIsNotAMap(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "operations.fake" option must be a map of operation names to values, "string" given.');

        new ImageUrlGenerator(new FakeProvider())->generate('og.jpg', operations: ['fake' => 'invalid']);
    }

    public function testItRejectsANonPositiveWidth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image width must be a positive integer, 0 given.');

        new ImageUrlGenerator(new FakeProvider())->generate('og.jpg', width: 0);
    }

    public function testBothDimensionsDefaultTheFitToCover(): void
    {
        self::assertSame('/og.jpg?w=1200&fm=&h=630&fit=cover', new ImageUrlGenerator(new FakeProvider())->generate('og.jpg', width: 1200, height: 630));
    }

    public function testAnOperationsKeyThatIsNotAnInstalledProviderIsRejected(): void
    {
        $generator = new ImageUrlGenerator(new FakeProvider(), ['null', 'fake', 'cloudflare']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "operations" option has a "cloudfare" key, which is not an installed image provider (installed: "null", "fake", "cloudflare").');

        $generator->generate('og.jpg', operations: ['cloudfare' => ['gravity' => 'auto']]);
    }

    public function testAnOperationsKeyIsCheckedEvenWithTheNullProvider(): void
    {
        $generator = new ImageUrlGenerator(new NullProvider(), ['null', 'cloudflare']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "operations" option has a "cloudfare" key');

        $generator->generate('og.jpg', operations: ['cloudfare' => ['gravity' => 'auto']]);
    }

    public function testAnInactiveInstalledProviderKeyIsAccepted(): void
    {
        $generator = new ImageUrlGenerator(new FakeProvider(), ['null', 'fake', 'cloudflare']);

        self::assertSame('/og.jpg?fm=', $generator->generate('og.jpg', operations: ['cloudflare' => ['gravity' => 'auto']]));
    }

    public function testWithoutAKnownProviderListAnyKeyIsAccepted(): void
    {
        self::assertSame('/og.jpg?fm=', new ImageUrlGenerator(new FakeProvider())->generate('og.jpg', operations: ['cloudfare' => []]));
    }

    public function testTheDefaultQualityAppliesWhenNoneIsGiven(): void
    {
        $generator = new ImageUrlGenerator(new FakeProvider(), null, 75);

        self::assertSame('/og.jpg?w=800&fm=&q=75', $generator->generate('og.jpg', width: 800));
    }

    public function testAnExplicitQualityWinsOverTheDefault(): void
    {
        $generator = new ImageUrlGenerator(new FakeProvider(), null, 75);

        self::assertSame('/og.jpg?w=800&fm=&q=90', $generator->generate('og.jpg', width: 800, quality: 90));
    }

    public function testTheNullProviderReturnsTheOriginalPath(): void
    {
        $url = new ImageUrlGenerator(new NullProvider())->generate('/uploads/og.jpg', width: 1200, format: 'webp');

        self::assertSame('/uploads/og.jpg', $url);
    }

    public function testAPresetFillsTheTransformation(): void
    {
        $generator = new ImageUrlGenerator(new FakeProvider(), presets: new ImagePresets(self::PRESETS));

        $url = $generator->generate('og.jpg', preset: 'thumbnail');

        self::assertSame('/og.jpg?w=200&fm=&h=200&fit=cover&q=70&sharpen=2', $url);
    }

    public function testAnExplicitValueWinsOverThePreset(): void
    {
        $generator = new ImageUrlGenerator(new FakeProvider(), presets: new ImagePresets(self::PRESETS));

        $url = $generator->generate('og.jpg', width: 300, operations: ['fake' => ['sharpen' => 5]], preset: 'thumbnail');

        self::assertSame('/og.jpg?w=300&fm=&h=200&fit=cover&q=70&sharpen=5', $url);
    }

    public function testThePresetQualityWinsOverTheDefaultOne(): void
    {
        $generator = new ImageUrlGenerator(new FakeProvider(), null, 75, new ImagePresets(self::PRESETS));

        $withPresetQuality = $generator->generate('og.jpg', preset: 'thumbnail');
        $withoutPresetQuality = $generator->generate('og.jpg', preset: 'wide');

        self::assertSame('/og.jpg?w=200&fm=&h=200&fit=cover&q=70&sharpen=2', $withPresetQuality);
        self::assertSame('/og.jpg?w=1200&fm=&q=75', $withoutPresetQuality);
    }

    public function testBothDimensionsDefaultTheFitToCoverAfterThePresetIsApplied(): void
    {
        $generator = new ImageUrlGenerator(new FakeProvider(), presets: new ImagePresets(self::PRESETS));

        $url = $generator->generate('og.jpg', height: 630, preset: 'wide');

        self::assertSame('/og.jpg?w=1200&fm=&h=630&fit=cover', $url);
    }

    public function testThePresetFitWinsOverTheCoverDefault(): void
    {
        $generator = new ImageUrlGenerator(new FakeProvider(), presets: new ImagePresets(self::PRESETS));

        $url = $generator->generate('og.jpg', preset: 'boxed');

        self::assertSame('/og.jpg?w=400&fm=&h=400&fit=contain', $url);
    }

    public function testAnUnknownPresetIsRejectedEvenWithTheNullProvider(): void
    {
        $generator = new ImageUrlGenerator(new NullProvider(), presets: new ImagePresets(self::PRESETS));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image preset "thumbnial" does not exist');

        $generator->generate('/uploads/og.jpg', preset: 'thumbnial');
    }

    public function testPresetOperationsAreCheckedAgainstTheInstalledProviders(): void
    {
        $presets = ['typo' => ['operations' => ['cloudfare' => ['gravity' => 'auto']]]];
        $generator = new ImageUrlGenerator(new FakeProvider(), ['null', 'fake'], null, new ImagePresets($presets));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "operations" option has a "cloudfare" key, which is not an installed image provider (installed: "null", "fake").');

        $generator->generate('og.jpg', preset: 'typo');
    }
}
