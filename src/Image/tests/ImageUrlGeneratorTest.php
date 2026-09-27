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
use Symfony\UX\Image\ImageUrlGenerator;
use Symfony\UX\Image\Provider\NullProvider;
use Symfony\UX\Image\Tests\Fixtures\FakeProvider;

final class ImageUrlGeneratorTest extends TestCase
{
    public function testItGeneratesOneUrlThroughTheProvider()
    {
        $url = new ImageUrlGenerator(new FakeProvider())->generate('og.jpg', width: 1200, height: 630, fit: Fit::Cover, format: 'jpeg', quality: 80);

        self::assertSame('/og.jpg?w=1200&fm=jpeg&h=630&fit=cover&q=80', $url);
    }

    public function testOnlyTheActiveProviderOperationsAreApplied()
    {
        $operations = ['fake' => ['sharpen' => 3], 'cloudflare' => ['gravity' => 'auto']];

        $url = new ImageUrlGenerator(new FakeProvider())->generate('og.jpg', width: 1200, operations: $operations);

        self::assertSame('/og.jpg?w=1200&fm=&sharpen=3', $url);
    }

    public function testItRejectsAFormatTheProviderCannotProduce()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image format "tiff" is not supported by the "fake" provider (supported: "avif", "webp", "jpeg").');

        new ImageUrlGenerator(new FakeProvider())->generate('og.jpg', format: 'tiff');
    }

    public function testTheAutoFormatNeedsAProviderThatNegotiatesIt()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image format "auto" is not supported by the "fake" provider (supported: "avif", "webp", "jpeg").');

        new ImageUrlGenerator(new FakeProvider(autoFormat: false))->generate('og.jpg', format: 'auto');
    }

    public function testTheAutoFormatIsAcceptedWhenTheProviderNegotiatesIt()
    {
        self::assertSame('/og.jpg?fm=auto', new ImageUrlGenerator(new FakeProvider())->generate('og.jpg', format: 'auto'));
    }

    public function testItRejectsAnOperationTheProviderDoesNotSupport()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image operation "gravity" is not supported by the "fake" provider (supported: "sharpen").');

        new ImageUrlGenerator(new FakeProvider())->generate('og.jpg', operations: ['fake' => ['gravity' => 'auto']]);
    }

    public function testItRejectsAnOperationsBlockThatIsNotAMap()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "operations.fake" option must be a map of operation names to values, "string" given.');

        new ImageUrlGenerator(new FakeProvider())->generate('og.jpg', operations: ['fake' => 'invalid']);
    }

    public function testItRejectsANonPositiveWidth()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image width must be a positive integer, 0 given.');

        new ImageUrlGenerator(new FakeProvider())->generate('og.jpg', width: 0);
    }

    public function testBothDimensionsDefaultTheFitToCover()
    {
        self::assertSame('/og.jpg?w=1200&fm=&h=630&fit=cover', new ImageUrlGenerator(new FakeProvider())->generate('og.jpg', width: 1200, height: 630));
    }

    public function testAnOperationsKeyThatIsNotAnInstalledProviderIsRejected()
    {
        $generator = new ImageUrlGenerator(new FakeProvider(), ['null', 'fake', 'cloudflare']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "operations" option has a "cloudfare" key, which is not an installed image provider (installed: "null", "fake", "cloudflare").');

        $generator->generate('og.jpg', operations: ['cloudfare' => ['gravity' => 'auto']]);
    }

    public function testAnOperationsKeyIsCheckedEvenWithTheNullProvider()
    {
        $generator = new ImageUrlGenerator(new NullProvider(), ['null', 'cloudflare']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "operations" option has a "cloudfare" key');

        $generator->generate('og.jpg', operations: ['cloudfare' => ['gravity' => 'auto']]);
    }

    public function testAnInactiveInstalledProviderKeyIsAccepted()
    {
        $generator = new ImageUrlGenerator(new FakeProvider(), ['null', 'fake', 'cloudflare']);

        self::assertSame('/og.jpg?fm=', $generator->generate('og.jpg', operations: ['cloudflare' => ['gravity' => 'auto']]));
    }

    public function testWithoutAKnownProviderListAnyKeyIsAccepted()
    {
        self::assertSame('/og.jpg?fm=', new ImageUrlGenerator(new FakeProvider())->generate('og.jpg', operations: ['cloudfare' => []]));
    }

    public function testTheNullProviderReturnsTheOriginalPath()
    {
        $url = new ImageUrlGenerator(new NullProvider())->generate('/uploads/og.jpg', width: 1200, format: 'webp');

        self::assertSame('/uploads/og.jpg', $url);
    }
}
