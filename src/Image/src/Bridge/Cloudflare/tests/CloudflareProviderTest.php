<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Cloudflare\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Bridge\Cloudflare\CloudflareProvider;
use Symfony\UX\Image\Bridge\Cloudflare\CloudflareProviderFactory;
use Symfony\UX\Image\Exception\IncompleteDsnException;
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Layout;
use Symfony\UX\Image\Provider\Dsn;
use Symfony\UX\Image\Renderer\RenderOptions;

final class CloudflareProviderTest extends TestCase
{
    #[DataProvider('provideUrls')]
    public function testItGeneratesTheExpectedUrl(ImageTransformation $transformation, string $expected)
    {
        self::assertSame($expected, new CloudflareProvider('cdn.example.com')->generateUrl($transformation));
    }

    public static function provideUrls(): iterable
    {
        yield 'width only' => [
            new ImageTransformation('hero.jpg', width: 800),
            'https://cdn.example.com/cdn-cgi/image/width=800/hero.jpg',
        ];
        yield 'every common parameter' => [
            new ImageTransformation('a/hero.jpg', width: 800, height: 450, fit: Fit::Cover, format: 'auto', quality: 80),
            'https://cdn.example.com/cdn-cgi/image/width=800,height=450,fit=cover,format=auto,quality=80/a/hero.jpg',
        ];
        yield 'scale down maps to scale-down' => [
            new ImageTransformation('hero.jpg', width: 800, fit: Fit::ScaleDown),
            'https://cdn.example.com/cdn-cgi/image/width=800,fit=scale-down/hero.jpg',
        ];
        yield 'provider operation' => [
            new ImageTransformation('hero.jpg', width: 800, operations: ['gravity' => 'auto']),
            'https://cdn.example.com/cdn-cgi/image/width=800,gravity=auto/hero.jpg',
        ];
        yield 'leading slash in the path is not doubled' => [
            new ImageTransformation('/hero.jpg', width: 800),
            'https://cdn.example.com/cdn-cgi/image/width=800/hero.jpg',
        ];
        yield 'a space in a path segment is percent-encoded' => [
            new ImageTransformation('hero image.jpg', width: 800),
            'https://cdn.example.com/cdn-cgi/image/width=800/hero%20image.jpg',
        ];
        yield 'a question mark in a path segment is encoded, not started as a query string' => [
            new ImageTransformation('a?b=1/hero.jpg', width: 800),
            'https://cdn.example.com/cdn-cgi/image/width=800/a%3Fb%3D1/hero.jpg',
        ];
        yield 'a hash in an operation value is encoded, not started as a fragment' => [
            new ImageTransformation('hero.jpg', width: 800, operations: ['background' => '#ff0000']),
            'https://cdn.example.com/cdn-cgi/image/width=800,background=%23ff0000/hero.jpg',
        ];
        yield 'a slash in an operation value is encoded, not ending the options segment' => [
            new ImageTransformation('hero.jpg', width: 800, operations: ['onerror' => 'redirect/../../evil']),
            'https://cdn.example.com/cdn-cgi/image/width=800,onerror=redirect%2F..%2F..%2Fevil/hero.jpg',
        ];
        yield 'a space in an operation value is encoded' => [
            new ImageTransformation('hero.jpg', width: 800, operations: ['metadata' => 'a b']),
            'https://cdn.example.com/cdn-cgi/image/width=800,metadata=a%20b/hero.jpg',
        ];
        yield 'no options at all falls back to the origin url, not an empty options segment' => [
            new ImageTransformation('hero.jpg'),
            'https://cdn.example.com/hero.jpg',
        ];
    }

    public function testWidthAndHeightBothGivenDefaultToACroppingFit()
    {
        $options = new RenderOptions(width: 800, height: 450);

        $url = new CloudflareProvider('cdn.example.com')->generateUrl(
            new ImageTransformation('hero.jpg', $options->width, $options->height, $options->fit),
        );

        self::assertStringContainsString('fit=cover', $url);
    }

    public function testHeightOnlyWithNoWidthLeavesFitAndWidthUnsetForTheProvidersOwnDefault()
    {
        $options = new RenderOptions(layout: Layout::FullWidth, height: 450);

        $url = new CloudflareProvider('cdn.example.com')->generateUrl(
            new ImageTransformation('hero.jpg', $options->width, $options->height, $options->fit),
        );

        self::assertSame('https://cdn.example.com/cdn-cgi/image/height=450/hero.jpg', $url);
    }

    public function testItAdvertisesAutoFormatSupport()
    {
        self::assertTrue(new CloudflareProvider('cdn.example.com')->supportsAutoFormat());
    }

    public function testItAdvertisesItsSupportedOperations()
    {
        self::assertSame(
            ['gravity', 'dpr', 'rotate', 'trim', 'blur', 'brightness', 'contrast', 'gamma', 'saturation', 'sharpen', 'background', 'border', 'anim', 'metadata', 'onerror', 'compression'],
            new CloudflareProvider('cdn.example.com')->getSupportedOperations(),
        );
    }

    public function testItAdvertisesItsSupportedFormats()
    {
        self::assertSame(['avif', 'webp', 'jpeg', 'png'], new CloudflareProvider('cdn.example.com')->getSupportedFormats());
    }

    public function testItAdvertisesItsName()
    {
        self::assertSame('cloudflare', new CloudflareProvider('cdn.example.com')->getName());
    }

    public function testTheFactoryRejectsAnotherScheme()
    {
        self::assertFalse(new CloudflareProviderFactory()->supports(new Dsn('keycdn://zone.kxcdn.com')));
    }

    public function testTheFactoryAcceptsItsOwnScheme()
    {
        self::assertTrue(new CloudflareProviderFactory()->supports(new Dsn('cloudflare://cdn.example.com')));
    }

    public function testTheFactoryRequiresAHost()
    {
        $this->expectException(IncompleteDsnException::class);
        $this->expectExceptionMessage('The Cloudflare image provider requires a host, e.g. "cloudflare://cdn.example.com".');

        new CloudflareProviderFactory()->create(new Dsn('cloudflare:'));
    }

    public function testAnEmptyAuthorityDsnFailsInDsnItselfNotInTheFactory()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image provider DSN is invalid.');

        new CloudflareProviderFactory()->create(new Dsn('cloudflare://'));
    }

    public function testTheFactoryCreatesAConfiguredProvider()
    {
        $provider = new CloudflareProviderFactory()->create(new Dsn('cloudflare://cdn.example.com'));

        self::assertInstanceOf(CloudflareProvider::class, $provider);
        self::assertSame(
            'https://cdn.example.com/cdn-cgi/image/width=800/hero.jpg',
            $provider->generateUrl(new ImageTransformation('hero.jpg', width: 800)),
        );
    }
}
