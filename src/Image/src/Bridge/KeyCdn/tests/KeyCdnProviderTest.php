<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\KeyCdn\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Bridge\KeyCdn\KeyCdnProvider;
use Symfony\UX\Image\Bridge\KeyCdn\KeyCdnProviderFactory;
use Symfony\UX\Image\Exception\IncompleteDsnException;
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Layout;
use Symfony\UX\Image\Provider\Dsn;
use Symfony\UX\Image\Renderer\RenderOptions;

final class KeyCdnProviderTest extends TestCase
{
    #[DataProvider('provideUrls')]
    public function testItGeneratesTheExpectedUrl(ImageTransformation $transformation, string $expected): void
    {
        self::assertSame($expected, new KeyCdnProvider('zone.kxcdn.com')->generateUrl($transformation));
    }

    public static function provideUrls(): iterable
    {
        yield 'width only' => [
            new ImageTransformation('hero.jpg', width: 800),
            'https://zone.kxcdn.com/hero.jpg?width=800',
        ];
        yield 'every common parameter' => [
            new ImageTransformation('a/hero.jpg', width: 800, height: 450, fit: Fit::Cover, format: 'webp', quality: 80),
            'https://zone.kxcdn.com/a/hero.jpg?width=800&height=450&fit=cover&format=webp&quality=80',
        ];
        yield 'provider operation' => [
            new ImageTransformation('hero.jpg', width: 800, operations: ['grayscale' => 1]),
            'https://zone.kxcdn.com/hero.jpg?width=800&grayscale=1',
        ];
        yield 'leading slash in the path is not doubled' => [
            new ImageTransformation('/hero.jpg', width: 800),
            'https://zone.kxcdn.com/hero.jpg?width=800',
        ];
        yield 'a space in a path segment is percent-encoded' => [
            new ImageTransformation('hero image.jpg', width: 800),
            'https://zone.kxcdn.com/hero%20image.jpg?width=800',
        ];
        yield 'a question mark in a path segment is encoded, not started as a query string' => [
            new ImageTransformation('a?b=1/hero.jpg', width: 800),
            'https://zone.kxcdn.com/a%3Fb%3D1/hero.jpg?width=800',
        ];
    }

    public function testWidthAndHeightBothGivenDefaultToACroppingFit(): void
    {
        $options = new RenderOptions(width: 800, height: 450);

        $url = new KeyCdnProvider('zone.kxcdn.com')->generateUrl(
            new ImageTransformation('hero.jpg', $options->width, $options->height, $options->fit),
        );

        self::assertStringContainsString('fit=cover', $url);
    }

    public function testHeightOnlyWithNoWidthLeavesFitAndWidthUnsetForTheProvidersOwnDefault(): void
    {
        $options = new RenderOptions(layout: Layout::FullWidth, height: 450);

        $url = new KeyCdnProvider('zone.kxcdn.com')->generateUrl(
            new ImageTransformation('hero.jpg', $options->width, $options->height, $options->fit),
        );

        self::assertSame('https://zone.kxcdn.com/hero.jpg?height=450', $url);
    }

    public function testItDoesNotSupportAutoFormat(): void
    {
        self::assertFalse(new KeyCdnProvider('zone.kxcdn.com')->supportsAutoFormat());
    }

    public function testItDoesNotAdvertiseAvif(): void
    {
        self::assertNotContains('avif', new KeyCdnProvider('zone.kxcdn.com')->getSupportedFormats());
    }

    public function testItAdvertisesItsSupportedOperations(): void
    {
        self::assertSame(
            ['position', 'enlarge', 'trim', 'crop', 'bg', 'rotate', 'flip', 'flop', 'sharpen', 'blur', 'gamma', 'grayscale', 'progressive', 'lossless', 'metadata'],
            new KeyCdnProvider('zone.kxcdn.com')->getSupportedOperations(),
        );
    }

    public function testItAdvertisesItsSupportedFormats(): void
    {
        self::assertSame(['webp', 'jpeg', 'png'], new KeyCdnProvider('zone.kxcdn.com')->getSupportedFormats());
    }

    public function testItAdvertisesItsName(): void
    {
        self::assertSame('keycdn', new KeyCdnProvider('zone.kxcdn.com')->getName());
    }

    public function testTheFactoryRejectsAnotherScheme(): void
    {
        self::assertFalse(new KeyCdnProviderFactory()->supports(new Dsn('cloudflare://cdn.example.com')));
    }

    public function testTheFactoryAcceptsItsOwnScheme(): void
    {
        self::assertTrue(new KeyCdnProviderFactory()->supports(new Dsn('keycdn://zone.kxcdn.com')));
    }

    public function testTheFactoryRequiresAHost(): void
    {
        $this->expectException(IncompleteDsnException::class);
        $this->expectExceptionMessage('The KeyCDN image provider requires a host, e.g. "keycdn://myzone.kxcdn.com".');

        new KeyCdnProviderFactory()->create(new Dsn('keycdn:'));
    }

    public function testAnEmptyAuthorityDsnFailsInDsnItselfNotInTheFactory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image provider DSN is invalid.');

        new KeyCdnProviderFactory()->create(new Dsn('keycdn://'));
    }

    public function testTheFactoryCreatesAConfiguredProvider(): void
    {
        $provider = new KeyCdnProviderFactory()->create(new Dsn('keycdn://zone.kxcdn.com'));

        self::assertInstanceOf(KeyCdnProvider::class, $provider);
        self::assertSame(
            'https://zone.kxcdn.com/hero.jpg?width=800',
            $provider->generateUrl(new ImageTransformation('hero.jpg', width: 800)),
        );
    }

    public function testTheFactoryRejectsAnUnknownOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "keycdn" image provider DSN: the provider takes no option, "driver" given.');

        new KeyCdnProviderFactory()->create(new Dsn('keycdn://myzone.kxcdn.com?driver=imagick'));
    }
}
