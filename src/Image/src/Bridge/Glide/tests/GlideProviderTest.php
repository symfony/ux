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

use League\Glide\Signatures\Signature;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\Image\Bridge\Glide\GlideProvider;
use Symfony\UX\Image\Bridge\Glide\GlideProviderFactory;
use Symfony\UX\Image\Exception\IncompleteDsnException;
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Provider\Dsn;

final class GlideProviderTest extends TestCase
{
    #[DataProvider('provideUrls')]
    public function testItGeneratesTheExpectedUrl(ImageTransformation $transformation, string $expected): void
    {
        self::assertSame($expected, new GlideProvider(null, '/img')->generateUrl($transformation));
    }

    public static function provideUrls(): iterable
    {
        yield 'width only' => [
            new ImageTransformation('hero.jpg', width: 800),
            '/img/hero.jpg?w=800',
        ];
        yield 'every common parameter' => [
            new ImageTransformation('a/hero.jpg', width: 800, height: 450, fit: Fit::Cover, format: 'webp', quality: 80),
            '/img/a/hero.jpg?w=800&h=450&fit=crop&fm=webp&q=80',
        ];
        yield 'contain' => [
            new ImageTransformation('hero.jpg', width: 800, height: 450, fit: Fit::Contain),
            '/img/hero.jpg?w=800&h=450&fit=contain',
        ];
        yield 'jpeg is spelled jpg' => [
            new ImageTransformation('hero.jpg', width: 800, format: 'jpeg'),
            '/img/hero.jpg?w=800&fm=jpg',
        ];
        yield 'provider operation' => [
            new ImageTransformation('hero.jpg', width: 800, operations: ['filt' => 'greyscale']),
            '/img/hero.jpg?w=800&filt=greyscale',
        ];
        yield 'an operation does not override a common parameter' => [
            new ImageTransformation('hero.jpg', width: 800, operations: ['w' => 10]),
            '/img/hero.jpg?w=800',
        ];
        yield 'leading slash in the path is not doubled' => [
            new ImageTransformation('/hero.jpg', width: 800),
            '/img/hero.jpg?w=800',
        ];
        yield 'a space in a path segment is percent-encoded' => [
            new ImageTransformation('hero image.jpg', width: 800),
            '/img/hero%20image.jpg?w=800',
        ];
        yield 'a question mark in a path segment is encoded, not started as a query string' => [
            new ImageTransformation('a?b=1/hero.jpg', width: 800),
            '/img/a%3Fb%3D1/hero.jpg?w=800',
        ];
        yield 'no transformation, no query string' => [
            new ImageTransformation('hero.jpg'),
            '/img/hero.jpg',
        ];
    }

    public function testItGeneratesAnAbsoluteUrlForAnImageHost(): void
    {
        self::assertSame(
            'https://images.example.com/img/hero.jpg?w=800',
            new GlideProvider('images.example.com', '/img')->generateUrl(new ImageTransformation('hero.jpg', width: 800)),
        );
    }

    public function testItGeneratesAUrlWithoutAPathPrefix(): void
    {
        self::assertSame(
            'https://images.example.com/hero.jpg?w=800',
            new GlideProvider('images.example.com')->generateUrl(new ImageTransformation('hero.jpg', width: 800)),
        );
    }

    public function testItSignsTheUrlWhenASignKeyIsGiven(): void
    {
        self::assertSame(
            '/img/hero.jpg?w=800&s=58281b8fe2ec4b139c138fd5db386ea3',
            new GlideProvider(null, '/img', 'mysecret')->generateUrl(new ImageTransformation('hero.jpg', width: 800)),
        );
    }

    #[DataProvider('provideSignedTransformations')]
    public function testGlideAcceptsTheSignatureOfTheRequestedUrl(?string $host, ImageTransformation $transformation): void
    {
        $request = Request::create(new GlideProvider($host, '/img', 'mysecret')->generateUrl($transformation));

        $this->expectNotToPerformAssertions();

        new Signature('mysecret')->validateRequest($request->getPathInfo(), $request->query->all());
    }

    public static function provideSignedTransformations(): iterable
    {
        yield 'every common parameter' => [null, new ImageTransformation('a/hero.jpg', width: 800, height: 450, fit: Fit::Cover, format: 'webp', quality: 80)];
        yield 'an operation sorted before the common parameters' => [null, new ImageTransformation('hero.jpg', width: 800, operations: ['bg' => 'black', 'markpos' => 'top-left'])];
        yield 'a value with characters to encode' => [null, new ImageTransformation('hero.jpg', width: 800, operations: ['mark' => 'logos/a b+c.png'])];
        yield 'a space in a path segment' => [null, new ImageTransformation('hero image.jpg', width: 800)];
        yield 'no transformation' => [null, new ImageTransformation('hero.jpg')];
        yield 'an image host' => ['images.example.com', new ImageTransformation('hero.jpg', width: 800)];
    }

    public function testGlideRejectsTheSignatureOfAnotherKey(): void
    {
        $request = Request::create(new GlideProvider(null, '/img', 'mysecret')->generateUrl(new ImageTransformation('hero.jpg', width: 800)));

        $this->expectExceptionMessage('Signature is not valid.');

        new Signature('another')->validateRequest($request->getPathInfo(), $request->query->all());
    }

    public function testItDoesNotSupportAutoFormat(): void
    {
        self::assertFalse(new GlideProvider(null)->supportsAutoFormat());
    }

    public function testItAdvertisesItsSupportedOperations(): void
    {
        self::assertSame(
            ['or', 'flip', 'crop', 'bri', 'con', 'gam', 'sharp', 'blur', 'pixel', 'filt', 'bg', 'border', 'dpr', 'mark', 'markw', 'markh', 'markx', 'marky', 'markpad', 'markfit', 'markpos', 'markalpha'],
            new GlideProvider(null)->getSupportedOperations(),
        );
    }

    public function testItAdvertisesItsSupportedFormats(): void
    {
        self::assertSame(['avif', 'webp', 'jpeg', 'png', 'gif'], new GlideProvider(null)->getSupportedFormats());
    }

    public function testItAdvertisesItsName(): void
    {
        self::assertSame('glide', new GlideProvider(null)->getName());
    }

    public function testTheFactoryRejectsAnotherScheme(): void
    {
        self::assertFalse(new GlideProviderFactory()->supports(new Dsn('imgix://my-source.imgix.net')));
    }

    public function testTheFactoryAcceptsItsOwnScheme(): void
    {
        self::assertTrue(new GlideProviderFactory()->supports(new Dsn('glide://default/img')));
    }

    public function testTheFactoryRequiresAHost(): void
    {
        $this->expectException(IncompleteDsnException::class);
        $this->expectExceptionMessage('The Glide image provider requires a host, e.g. "glide://default/img" or "glide://images.example.com/img".');

        new GlideProviderFactory()->create(new Dsn('glide:'));
    }

    #[DataProvider('provideDsns')]
    public function testTheFactoryCreatesAConfiguredProvider(string $dsn, string $expected): void
    {
        $provider = new GlideProviderFactory()->create(new Dsn($dsn));

        self::assertInstanceOf(GlideProvider::class, $provider);
        self::assertSame($expected, $provider->generateUrl(new ImageTransformation('hero.jpg', width: 800)));
    }

    public static function provideDsns(): iterable
    {
        yield 'the default host renders a relative URL' => ['glide://default/img', '/img/hero.jpg?w=800'];
        yield 'another host renders an absolute URL' => ['glide://images.example.com/img', 'https://images.example.com/img/hero.jpg?w=800'];
        yield 'a trailing slash on the prefix is not doubled' => ['glide://default/img/', '/img/hero.jpg?w=800'];
        yield 'a nested prefix' => ['glide://default/media/cache', '/media/cache/hero.jpg?w=800'];
        yield 'no prefix' => ['glide://default', '/hero.jpg?w=800'];
        yield 'the sign key' => ['glide://default/img?sign_key=mysecret', '/img/hero.jpg?w=800&s=58281b8fe2ec4b139c138fd5db386ea3'];
    }

    public function testTheFactoryRejectsAnEmptySignKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The option "sign_key" with value "" is invalid.');

        new GlideProviderFactory()->create(new Dsn('glide://default/img?sign_key='));
    }

    public function testTheFactoryRejectsAnUnknownOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The option "driver" does not exist. Defined options are: "sign_key".');

        new GlideProviderFactory()->create(new Dsn('glide://default/img?driver=imagick'));
    }
}
