<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Imgix\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Bridge\Imgix\ImgixProvider;
use Symfony\UX\Image\Bridge\Imgix\ImgixProviderFactory;
use Symfony\UX\Image\Exception\IncompleteDsnException;
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Provider\Dsn;

final class ImgixProviderTest extends TestCase
{
    #[DataProvider('provideUrls')]
    public function testItGeneratesTheExpectedUrl(ImageTransformation $transformation, string $expected): void
    {
        self::assertSame($expected, new ImgixProvider('demo.imgix.net')->generateUrl($transformation));
    }

    public static function provideUrls(): iterable
    {
        yield 'width only' => [
            new ImageTransformation('hero.jpg', width: 800),
            'https://demo.imgix.net/hero.jpg?w=800',
        ];
        yield 'every common parameter' => [
            new ImageTransformation('a/hero.jpg', width: 800, height: 450, fit: Fit::Cover, format: 'webp', quality: 80),
            'https://demo.imgix.net/a/hero.jpg?w=800&h=450&fit=crop&fm=webp&q=80',
        ];
        yield 'contain fit' => [
            new ImageTransformation('hero.jpg', width: 800, height: 450, fit: Fit::Contain),
            'https://demo.imgix.net/hero.jpg?w=800&h=450&fit=clip',
        ];
        yield 'jpeg is spelled jpg' => [
            new ImageTransformation('hero.jpg', width: 800, format: 'jpeg'),
            'https://demo.imgix.net/hero.jpg?w=800&fm=jpg',
        ];
        yield 'auto format' => [
            new ImageTransformation('hero.jpg', width: 800, format: 'auto'),
            'https://demo.imgix.net/hero.jpg?w=800&auto=format',
        ];
        yield 'auto format joins an auto operation' => [
            new ImageTransformation('hero.jpg', width: 800, format: 'auto', operations: ['auto' => 'compress']),
            'https://demo.imgix.net/hero.jpg?w=800&auto=format%2Ccompress',
        ];
        yield 'an auto operation without auto format' => [
            new ImageTransformation('hero.jpg', width: 800, operations: ['auto' => 'compress']),
            'https://demo.imgix.net/hero.jpg?w=800&auto=compress',
        ];
        yield 'provider operation' => [
            new ImageTransformation('hero.jpg', width: 800, operations: ['sat' => -100, 'fill-color' => 'ff0000']),
            'https://demo.imgix.net/hero.jpg?w=800&sat=-100&fill-color=ff0000',
        ];
        yield 'no parameter' => [
            new ImageTransformation('hero.jpg'),
            'https://demo.imgix.net/hero.jpg',
        ];
        yield 'leading slash in the path is not doubled' => [
            new ImageTransformation('/hero.jpg', width: 800),
            'https://demo.imgix.net/hero.jpg?w=800',
        ];
        yield 'a space in a path segment is percent-encoded' => [
            new ImageTransformation('hero image.jpg', width: 800),
            'https://demo.imgix.net/hero%20image.jpg?w=800',
        ];
        yield 'a question mark in a path segment is encoded, not started as a query string' => [
            new ImageTransformation('a?b=1/hero.jpg', width: 800),
            'https://demo.imgix.net/a%3Fb%3D1/hero.jpg?w=800',
        ];
        yield 'an operation value is percent-encoded' => [
            new ImageTransformation('hero.jpg', width: 800, operations: ['rect' => '0,0,100,100']),
            'https://demo.imgix.net/hero.jpg?w=800&rect=0%2C0%2C100%2C100',
        ];
    }

    #[DataProvider('provideSdkSignatures')]
    public function testItMatchesTheSignaturesOfTheImgixSdk(ImageTransformation $transformation, string $expected): void
    {
        self::assertSame($expected, new ImgixProvider('imgix-library-secure-test-source.imgix.net', 'EHFQXiZhxP4wA2c4')->generateUrl($transformation));
    }

    public static function provideSdkSignatures(): iterable
    {
        yield 'with a parameter' => [
            new ImageTransformation('dog.jpg', width: 500),
            'https://imgix-library-secure-test-source.imgix.net/dog.jpg?w=500&s=e4eb402d12bbdf267bf0fc5588170d56',
        ];
        yield 'without a parameter' => [
            new ImageTransformation('dog.jpg'),
            'https://imgix-library-secure-test-source.imgix.net/dog.jpg?s=2b0bc99b1042e3c1c9aae6598acc3def',
        ];
    }

    public function testTheSignatureCoversTheEncodedPathAndQuery(): void
    {
        self::assertSame(
            'https://demo.imgix.net/hero%20image.jpg?w=800&h=450&fit=crop&s=884e765cec4c78cac8e88992411e5835',
            new ImgixProvider('demo.imgix.net', 'secret')->generateUrl(new ImageTransformation('hero image.jpg', 800, 450, Fit::Cover)),
        );
    }

    public function testItAdvertisesAutoFormatSupport(): void
    {
        self::assertTrue(new ImgixProvider('demo.imgix.net')->supportsAutoFormat());
    }

    public function testItAdvertisesItsSupportedOperations(): void
    {
        self::assertSame(
            ['auto', 'bg', 'blur', 'border', 'bri', 'con', 'crop', 'dpr', 'exp', 'fill', 'fill-color', 'flip', 'fp-x', 'fp-y', 'fp-z', 'gam', 'high', 'invert', 'monochrome', 'orient', 'pad', 'rect', 'rot', 'sat', 'sepia', 'shad', 'sharp', 'trim', 'usm', 'vib'],
            new ImgixProvider('demo.imgix.net')->getSupportedOperations(),
        );
    }

    public function testItAdvertisesItsSupportedFormats(): void
    {
        self::assertSame(['avif', 'webp', 'jpeg', 'png'], new ImgixProvider('demo.imgix.net')->getSupportedFormats());
    }

    public function testItAdvertisesItsName(): void
    {
        self::assertSame('imgix', new ImgixProvider('demo.imgix.net')->getName());
    }

    public function testTheFactoryRejectsAnotherScheme(): void
    {
        self::assertFalse(new ImgixProviderFactory()->supports(new Dsn('cloudflare://cdn.example.com')));
    }

    public function testTheFactoryAcceptsItsOwnScheme(): void
    {
        self::assertTrue(new ImgixProviderFactory()->supports(new Dsn('imgix://demo.imgix.net')));
    }

    public function testTheFactoryRequiresAHost(): void
    {
        $this->expectException(IncompleteDsnException::class);
        $this->expectExceptionMessage('The imgix image provider requires a source domain, e.g. "imgix://my-source.imgix.net".');

        new ImgixProviderFactory()->create(new Dsn('imgix:'));
    }

    public function testTheFactoryCreatesAConfiguredProvider(): void
    {
        $provider = new ImgixProviderFactory()->create(new Dsn('imgix://demo.imgix.net'));

        self::assertInstanceOf(ImgixProvider::class, $provider);
        self::assertSame('https://demo.imgix.net/hero.jpg?w=800', $provider->generateUrl(new ImageTransformation('hero.jpg', width: 800)));
    }

    public function testTheFactoryPassesTheSignKeyOn(): void
    {
        $provider = new ImgixProviderFactory()->create(new Dsn('imgix://imgix-library-secure-test-source.imgix.net?sign_key=EHFQXiZhxP4wA2c4'));

        self::assertSame(
            'https://imgix-library-secure-test-source.imgix.net/dog.jpg?w=500&s=e4eb402d12bbdf267bf0fc5588170d56',
            $provider->generateUrl(new ImageTransformation('dog.jpg', width: 500)),
        );
    }

    public function testTheFactoryRejectsAnEmptySignKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The option "sign_key" with value "" is invalid.');

        new ImgixProviderFactory()->create(new Dsn('imgix://demo.imgix.net?sign_key='));
    }

    public function testTheFactoryRejectsAnUnknownOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The option "driver" does not exist. Defined options are: "sign_key".');

        new ImgixProviderFactory()->create(new Dsn('imgix://demo.imgix.net?driver=imagick'));
    }
}
