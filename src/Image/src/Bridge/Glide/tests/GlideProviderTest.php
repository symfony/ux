<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Glide\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Bridge\Glide\GlideProvider;
use Symfony\UX\Image\Bridge\Glide\GlideProviderFactory;
use Symfony\UX\Image\Exception\IncompleteDsnException;
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Layout;
use Symfony\UX\Image\Provider\Dsn;
use Symfony\UX\Image\Renderer\ImageRenderer;
use Symfony\UX\Image\Renderer\LayoutResolver;
use Symfony\UX\Image\Renderer\RenderOptions;

final class GlideProviderTest extends TestCase
{
    private const string DSN = 'glide://default/images?source=/tmp/source&cache=/tmp/cache';

    #[DataProvider('provideUrls')]
    public function testItGeneratesTheExpectedUrl(ImageTransformation $transformation, string $expected): void
    {
        self::assertSame($expected, new GlideProvider('/images')->generateUrl($transformation));
    }

    public static function provideUrls(): iterable
    {
        yield 'width only' => [
            new ImageTransformation('hero.jpg', width: 800),
            '/images/hero.jpg?w=800',
        ];
        yield 'every common parameter' => [
            new ImageTransformation('hero.jpg', width: 800, height: 450, fit: Fit::Cover, format: 'webp', quality: 80),
            '/images/hero.jpg?w=800&h=450&fit=crop&fm=webp&q=80',
        ];
        yield 'a pinned "jpeg" becomes Glide\'s own "jpg"' => [
            new ImageTransformation('hero.jpg', width: 800, format: 'jpeg'),
            '/images/hero.jpg?w=800&fm=jpg',
        ];
        yield 'a pinned "jpg" is passed through' => [
            new ImageTransformation('hero.jpg', width: 800, format: 'jpg'),
            '/images/hero.jpg?w=800&fm=jpg',
        ];
        yield 'contain maps to contain' => [
            new ImageTransformation('a.jpg', width: 10, fit: Fit::Contain),
            '/images/a.jpg?w=10&fit=contain',
        ];
        yield 'provider operation' => [
            new ImageTransformation('hero.jpg', width: 800, operations: ['blur' => 25]),
            '/images/hero.jpg?w=800&blur=25',
        ];
        yield 'leading slash in the path is not doubled' => [
            new ImageTransformation('/hero.jpg', width: 800),
            '/images/hero.jpg?w=800',
        ];
        yield 'a space in a path segment is percent-encoded' => [
            new ImageTransformation('hero image.jpg', width: 800),
            '/images/hero%20image.jpg?w=800',
        ];
        yield 'a question mark in a path segment is encoded, not started as a query string' => [
            new ImageTransformation('a?b=1/hero.jpg', width: 800),
            '/images/a%3Fb%3D1/hero.jpg?w=800',
        ];
    }

    public function testItGeneratesAUrlUnderTheConfiguredPrefix(): void
    {
        $provider = new GlideProvider('/images');

        self::assertSame(
            '/images/hero.jpg?w=800&h=450&fit=crop&fm=webp&q=80',
            $provider->generateUrl(new ImageTransformation('hero.jpg', width: 800, height: 450, fit: Fit::Cover, format: 'webp', quality: 80)),
        );
    }

    public function testCoverMapsToCrop(): void
    {
        $provider = new GlideProvider('/images');

        self::assertStringContainsString('fit=crop', $provider->generateUrl(new ImageTransformation('a.jpg', width: 10, fit: Fit::Cover)));
    }

    public function testWidthAndHeightBothGivenDefaultToACroppingFit(): void
    {
        $options = new RenderOptions(width: 800, height: 450);

        $url = new GlideProvider('/images')->generateUrl(
            new ImageTransformation('hero.jpg', $options->width, $options->height, $options->fit),
        );

        self::assertStringContainsString('fit=crop', $url);
    }

    public function testHeightOnlyWithNoWidthLeavesFitAndWidthUnsetForTheProvidersOwnDefault(): void
    {
        $options = new RenderOptions(layout: Layout::FullWidth, height: 450);

        $url = new GlideProvider('/images')->generateUrl(
            new ImageTransformation('hero.jpg', $options->width, $options->height, $options->fit),
        );

        self::assertSame('/images/hero.jpg?h=450', $url);
    }

    public function testItAppendsASignatureWhenASignKeyIsConfigured(): void
    {
        $unsigned = new GlideProvider('/images')->generateUrl(new ImageTransformation('hero.jpg', width: 800));
        $signed = new GlideProvider('/images', 's3cret')->generateUrl(new ImageTransformation('hero.jpg', width: 800));

        self::assertSame('/images/hero.jpg?w=800', $unsigned);
        self::assertMatchesRegularExpression('#^/images/hero\.jpg\?w=800&s=[0-9a-f]{32}$#', $signed);
    }

    public function testItSupportsAutoFormatBecauseTheControllerNegotiates(): void
    {
        self::assertTrue(new GlideProvider('/images')->supportsAutoFormat());
    }

    public function testItAdvertisesItsSupportedOperations(): void
    {
        self::assertSame(
            ['crop', 'or', 'bri', 'con', 'gam', 'sharp', 'blur', 'pixel', 'filt', 'bg'],
            new GlideProvider('/images')->getSupportedOperations(),
        );
    }

    public function testItOnlyAdvertisesTheFormatsItWasGiven(): void
    {
        self::assertSame(['webp', 'jpeg'], new GlideProvider('/images', null, ['webp', 'jpeg'])->getSupportedFormats());
    }

    public function testItAdvertisesItsSupportedFormats(): void
    {
        self::assertSame(['avif', 'webp', 'jpeg', 'pjpg', 'png', 'gif'], new GlideProvider('/images')->getSupportedFormats());
    }

    public function testToGlideFormatTranslatesJpegAndLeavesEveryOtherFormatUnchanged(): void
    {
        self::assertSame('jpg', GlideProvider::toGlideFormat('jpeg'));
        self::assertSame('jpg', GlideProvider::toGlideFormat('jpg'));
        self::assertSame('avif', GlideProvider::toGlideFormat('avif'));
        self::assertSame('auto', GlideProvider::toGlideFormat('auto'));
    }

    public function testAPinnedJpegFormatIsAcceptedByImageRendererAndReachesTheGeneratedUrlAsFmJpg(): void
    {
        $renderer = new ImageRenderer(new GlideProvider('/images'), new LayoutResolver());

        $rendered = $renderer->render('hero.jpg', 'Hero', new RenderOptions(layout: Layout::Fixed, width: 800, format: 'jpeg'));

        self::assertStringContainsString('fm=jpg', $rendered->imgAttributes['src']);
        self::assertStringContainsString('fm=jpg', $rendered->imgAttributes['srcset']);
    }

    public function testItAdvertisesItsName(): void
    {
        self::assertSame('glide', new GlideProvider('/images')->getName());
    }

    public function testTheFactoryRejectsAnotherScheme(): void
    {
        self::assertFalse(new GlideProviderFactory()->supports(new Dsn('cloudflare://cdn.example.com')));
    }

    public function testTheFactoryAcceptsItsOwnScheme(): void
    {
        self::assertTrue(new GlideProviderFactory()->supports(new Dsn('glide://default/images')));
    }

    public function testTheFactoryRequiresAUrlPrefix(): void
    {
        $this->expectException(IncompleteDsnException::class);
        $this->expectExceptionMessage('The Glide image provider requires a URL prefix, e.g. "glide://default/images".');

        new GlideProviderFactory()->create(new Dsn('glide:'));
    }

    public function testTheFactoryRequiresAUrlPrefixEvenWhenAHostIsPresent(): void
    {
        $this->expectException(IncompleteDsnException::class);
        $this->expectExceptionMessage('The Glide image provider requires a URL prefix, e.g. "glide://default/images".');

        new GlideProviderFactory()->create(new Dsn('glide://default'));
    }

    public function testAnEmptyAuthorityDsnFailsInDsnItselfNotInTheFactory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image provider DSN is invalid.');

        new GlideProviderFactory()->create(new Dsn('glide://'));
    }

    public function testTheFactoryRequiresASourceDirectory(): void
    {
        $this->expectException(IncompleteDsnException::class);
        $this->expectExceptionMessage('The Glide image provider requires a "source" directory, e.g. "glide://default/images?source=/app/public&cache=/app/var/glide-cache".');

        new GlideProviderFactory()->create(new Dsn('glide://default/images?cache=/tmp/cache'));
    }

    public function testTheFactoryRequiresACacheDirectory(): void
    {
        $this->expectException(IncompleteDsnException::class);
        $this->expectExceptionMessage('The Glide image provider requires a "cache" directory, e.g. "glide://default/images?source=/app/public&cache=/app/var/glide-cache".');

        new GlideProviderFactory()->create(new Dsn('glide://default/images?source=/tmp/source'));
    }

    public function testTheFactoryRejectsAnUnknownOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "glide" image provider DSN: The option "sign_kye" does not exist. Defined options are: "cache", "driver", "max_age", "max_image_size", "sign_key", "source", "visibility".');

        new GlideProviderFactory()->create(new Dsn(self::DSN.'&sign_kye=s3cret'));
    }

    public function testTheFactoryCreatesAConfiguredProvider(): void
    {
        $provider = new GlideProviderFactory()->create(new Dsn(self::DSN));

        self::assertInstanceOf(GlideProvider::class, $provider);
        self::assertSame(
            '/images/hero.jpg?w=800',
            $provider->generateUrl(new ImageTransformation('hero.jpg', width: 800)),
        );
    }

    public function testTheFactoryReadsTheSignKeyFromTheDsn(): void
    {
        $provider = new GlideProviderFactory()->create(new Dsn(self::DSN.'&sign_key=s3cret'));

        self::assertMatchesRegularExpression(
            '#^/images/hero\.jpg\?w=800&s=[0-9a-f]{32}$#',
            $provider->generateUrl(new ImageTransformation('hero.jpg', width: 800)),
        );
    }
}
