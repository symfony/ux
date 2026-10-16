<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Ipx\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Bridge\Ipx\IpxProvider;
use Symfony\UX\Image\Bridge\Ipx\IpxProviderFactory;
use Symfony\UX\Image\Exception\IncompleteDsnException;
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Provider\Dsn;

final class IpxProviderTest extends TestCase
{
    #[DataProvider('provideUrls')]
    public function testItGeneratesTheExpectedUrl(ImageTransformation $transformation, string $expected): void
    {
        self::assertSame($expected, new IpxProvider('images.example.com')->generateUrl($transformation));
    }

    public static function provideUrls(): iterable
    {
        yield 'width only' => [
            new ImageTransformation('hero.jpg', width: 800),
            'https://images.example.com/w_800/hero.jpg',
        ];
        yield 'height only' => [
            new ImageTransformation('hero.jpg', height: 450),
            'https://images.example.com/h_450/hero.jpg',
        ];
        yield 'both dimensions are one resize' => [
            new ImageTransformation('hero.jpg', width: 800, height: 450),
            'https://images.example.com/s_800x450/hero.jpg',
        ];
        yield 'every common parameter' => [
            new ImageTransformation('a/hero.jpg', width: 800, height: 450, fit: Fit::Cover, format: 'webp', quality: 80),
            'https://images.example.com/s_800x450,fit_cover,f_webp,q_80/a/hero.jpg',
        ];
        yield 'contain fit does not pad the image' => [
            new ImageTransformation('hero.jpg', width: 800, height: 450, fit: Fit::Contain),
            'https://images.example.com/s_800x450,fit_inside/hero.jpg',
        ];
        yield 'auto format' => [
            new ImageTransformation('hero.jpg', width: 800, format: 'auto'),
            'https://images.example.com/w_800,f_auto/hero.jpg',
        ];
        yield 'provider operation' => [
            new ImageTransformation('hero.jpg', width: 800, operations: ['blur' => 5, 'position' => 'right-top']),
            'https://images.example.com/w_800,blur_5,position_right-top/hero.jpg',
        ];
        yield 'an operation with several arguments' => [
            new ImageTransformation('hero.jpg', width: 800, operations: ['extract' => '0_0_800_450']),
            'https://images.example.com/w_800,extract_0_0_800_450/hero.jpg',
        ];
        yield 'a true operation is a flag' => [
            new ImageTransformation('hero.jpg', width: 800, operations: ['grayscale' => true]),
            'https://images.example.com/w_800,grayscale/hero.jpg',
        ];
        yield 'a false operation is passed as false' => [
            new ImageTransformation('hero.jpg', width: 800, operations: ['negate' => false]),
            'https://images.example.com/w_800,negate_false/hero.jpg',
        ];
        yield 'a comma in an operation value is encoded, not read as another modifier' => [
            new ImageTransformation('hero.jpg', width: 800, operations: ['background' => 'red,w_5000']),
            'https://images.example.com/w_800,background_red%2Cw_5000/hero.jpg',
        ];
        yield 'no parameter' => [
            new ImageTransformation('hero.jpg'),
            'https://images.example.com/_/hero.jpg',
        ];
        yield 'leading slash in the path is not doubled' => [
            new ImageTransformation('/hero.jpg', width: 800),
            'https://images.example.com/w_800/hero.jpg',
        ];
        yield 'a space in a path segment is percent-encoded' => [
            new ImageTransformation('hero image.jpg', width: 800),
            'https://images.example.com/w_800/hero%20image.jpg',
        ];
        yield 'a question mark in a path segment is encoded, not started as a query string' => [
            new ImageTransformation('a?b=1/hero.jpg', width: 800),
            'https://images.example.com/w_800/a%3Fb%3D1/hero.jpg',
        ];
    }

    public function testItPrefixesTheUrlWithTheBasePath(): void
    {
        self::assertSame(
            'https://example.com/_ipx/w_800/hero.jpg',
            new IpxProvider('example.com', basePath: '/_ipx')->generateUrl(new ImageTransformation('hero.jpg', width: 800)),
        );
    }

    public function testItKeepsThePort(): void
    {
        self::assertSame(
            'https://images.example.com:8443/w_800/hero.jpg',
            new IpxProvider('images.example.com', 8443)->generateUrl(new ImageTransformation('hero.jpg', width: 800)),
        );
    }

    public function testItUsesPlainHttpWhenNotSecure(): void
    {
        self::assertSame(
            'http://127.0.0.1:3000/w_800/hero.jpg',
            new IpxProvider('127.0.0.1', 3000, secure: false)->generateUrl(new ImageTransformation('hero.jpg', width: 800)),
        );
    }

    public function testItAdvertisesAutoFormatSupport(): void
    {
        self::assertTrue(new IpxProvider('images.example.com')->supportsAutoFormat());
    }

    public function testItAdvertisesItsSupportedOperations(): void
    {
        self::assertSame(
            ['animated', 'autoorient', 'background', 'blur', 'brightness', 'clahe', 'dilate', 'enlarge', 'erode', 'extend', 'extract', 'flatten', 'flip', 'flop', 'gamma', 'grayscale', 'hue', 'kernel', 'lightness', 'linear', 'median', 'modulate', 'negate', 'normalize', 'opacity', 'position', 'rotate', 'saturation', 'sharpen', 'threshold', 'tint', 'trim', 'unflatten'],
            new IpxProvider('images.example.com')->getSupportedOperations(),
        );
    }

    public function testItAdvertisesItsSupportedFormats(): void
    {
        self::assertSame(['avif', 'webp', 'jpeg', 'png'], new IpxProvider('images.example.com')->getSupportedFormats());
    }

    public function testItAdvertisesItsName(): void
    {
        self::assertSame('ipx', new IpxProvider('images.example.com')->getName());
    }

    public function testTheFactoryRejectsAnotherScheme(): void
    {
        self::assertFalse(new IpxProviderFactory()->supports(new Dsn('cloudflare://cdn.example.com')));
    }

    public function testTheFactoryAcceptsItsOwnScheme(): void
    {
        self::assertTrue(new IpxProviderFactory()->supports(new Dsn('ipx://images.example.com')));
    }

    public function testTheFactoryRequiresAHost(): void
    {
        $this->expectException(IncompleteDsnException::class);
        $this->expectExceptionMessage('The IPX image provider requires a host, e.g. "ipx://images.example.com".');

        new IpxProviderFactory()->create(new Dsn('ipx:'));
    }

    public function testTheFactoryCreatesAConfiguredProvider(): void
    {
        $provider = new IpxProviderFactory()->create(new Dsn('ipx://images.example.com'));

        self::assertInstanceOf(IpxProvider::class, $provider);
        self::assertSame('https://images.example.com/w_800/hero.jpg', $provider->generateUrl(new ImageTransformation('hero.jpg', width: 800)));
    }

    public function testTheFactoryPassesTheBasePathOn(): void
    {
        $provider = new IpxProviderFactory()->create(new Dsn('ipx://example.com/_ipx/'));

        self::assertSame('https://example.com/_ipx/w_800/hero.jpg', $provider->generateUrl(new ImageTransformation('hero.jpg', width: 800)));
    }

    public function testTheFactoryPassesThePortOn(): void
    {
        $provider = new IpxProviderFactory()->create(new Dsn('ipx://images.example.com:8443/_ipx'));

        self::assertSame('https://images.example.com:8443/_ipx/w_800/hero.jpg', $provider->generateUrl(new ImageTransformation('hero.jpg', width: 800)));
    }

    public function testTheFactoryUsesPlainHttpWhenNotSecure(): void
    {
        $provider = new IpxProviderFactory()->create(new Dsn('ipx://127.0.0.1:3000?secure=false'));

        self::assertSame('http://127.0.0.1:3000/w_800/hero.jpg', $provider->generateUrl(new ImageTransformation('hero.jpg', width: 800)));
    }

    public function testTheFactoryUsesHttpsWhenSecure(): void
    {
        $provider = new IpxProviderFactory()->create(new Dsn('ipx://images.example.com?secure=true'));

        self::assertSame('https://images.example.com/w_800/hero.jpg', $provider->generateUrl(new ImageTransformation('hero.jpg', width: 800)));
    }

    public function testTheFactoryRejectsAnInvalidSecureValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The option "secure" with value "no" is invalid. Accepted values are: "true", "false".');

        new IpxProviderFactory()->create(new Dsn('ipx://images.example.com?secure=no'));
    }

    public function testTheFactoryRejectsAnUnknownOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The option "driver" does not exist. Defined options are: "secure".');

        new IpxProviderFactory()->create(new Dsn('ipx://images.example.com?driver=imagick'));
    }
}
