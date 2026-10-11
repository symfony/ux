<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Cloudinary\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Bridge\Cloudinary\CloudinaryProvider;
use Symfony\UX\Image\Bridge\Cloudinary\CloudinaryProviderFactory;
use Symfony\UX\Image\Exception\IncompleteDsnException;
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Provider\Dsn;

final class CloudinaryProviderTest extends TestCase
{
    #[DataProvider('provideUrls')]
    public function testItGeneratesTheExpectedUrl(ImageTransformation $transformation, string $expected): void
    {
        self::assertSame($expected, new CloudinaryProvider('demo', 'https://example.com')->generateUrl($transformation));
    }

    public static function provideUrls(): iterable
    {
        yield 'width only' => [
            new ImageTransformation('hero.jpg', width: 800),
            'https://res.cloudinary.com/demo/image/fetch/w_800/https://example.com/hero.jpg',
        ];
        yield 'every common parameter' => [
            new ImageTransformation('a/hero.jpg', width: 800, height: 450, fit: Fit::Cover, format: 'auto', quality: 80),
            'https://res.cloudinary.com/demo/image/fetch/w_800,h_450,c_fill,f_auto,q_80/https://example.com/a/hero.jpg',
        ];
        yield 'contain fits inside the box' => [
            new ImageTransformation('hero.jpg', width: 800, height: 450, fit: Fit::Contain),
            'https://res.cloudinary.com/demo/image/fetch/w_800,h_450,c_fit/https://example.com/hero.jpg',
        ];
        yield 'jpeg is spelled jpg' => [
            new ImageTransformation('hero.png', width: 800, format: 'jpeg'),
            'https://res.cloudinary.com/demo/image/fetch/w_800,f_jpg/https://example.com/hero.png',
        ];
        yield 'provider operations' => [
            new ImageTransformation('hero.jpg', width: 800, operations: ['g' => 'auto', 'e' => 'sharpen:100']),
            'https://res.cloudinary.com/demo/image/fetch/w_800,g_auto,e_sharpen:100/https://example.com/hero.jpg',
        ];
        yield 'leading slash in the path is not doubled' => [
            new ImageTransformation('/uploads/hero.jpg', width: 800),
            'https://res.cloudinary.com/demo/image/fetch/w_800/https://example.com/uploads/hero.jpg',
        ];
        yield 'a space in a path segment is percent-encoded' => [
            new ImageTransformation('hero image.jpg', width: 800),
            'https://res.cloudinary.com/demo/image/fetch/w_800/https://example.com/hero%20image.jpg',
        ];
        yield 'a question mark in a path segment is encoded, not started as a query string' => [
            new ImageTransformation('a?b=1/hero.jpg', width: 800),
            'https://res.cloudinary.com/demo/image/fetch/w_800/https://example.com/a%3Fb%3D1/hero.jpg',
        ];
        yield 'a hash in an operation value is encoded, not started as a fragment' => [
            new ImageTransformation('hero.jpg', width: 800, operations: ['b' => '#ff0000']),
            'https://res.cloudinary.com/demo/image/fetch/w_800,b_%23ff0000/https://example.com/hero.jpg',
        ];
        yield 'no transformation at all still goes through Cloudinary' => [
            new ImageTransformation('hero.jpg'),
            'https://res.cloudinary.com/demo/image/fetch/https://example.com/hero.jpg',
        ];
    }

    #[DataProvider('provideUploadUrls')]
    public function testWithoutAnOriginItUsesUploadDelivery(ImageTransformation $transformation, string $expected): void
    {
        self::assertSame($expected, new CloudinaryProvider('demo')->generateUrl($transformation));
    }

    public static function provideUploadUrls(): iterable
    {
        yield 'width only' => [
            new ImageTransformation('hero.jpg', width: 800),
            'https://res.cloudinary.com/demo/image/upload/w_800/hero.jpg',
        ];
        yield 'every common parameter' => [
            new ImageTransformation('uploads/hero.jpg', width: 800, height: 450, fit: Fit::Cover, format: 'auto', quality: 80),
            'https://res.cloudinary.com/demo/image/upload/w_800,h_450,c_fill,f_auto,q_80/uploads/hero.jpg',
        ];
        yield 'the leading slash is not part of the public id' => [
            new ImageTransformation('/uploads/hero.jpg', width: 800),
            'https://res.cloudinary.com/demo/image/upload/w_800/uploads/hero.jpg',
        ];
        yield 'a space in a path segment is percent-encoded' => [
            new ImageTransformation('hero image.jpg', width: 800),
            'https://res.cloudinary.com/demo/image/upload/w_800/hero%20image.jpg',
        ];
        yield 'no transformation at all' => [
            new ImageTransformation('hero.jpg'),
            'https://res.cloudinary.com/demo/image/upload/hero.jpg',
        ];
    }

    public function testTheOriginKeepsItsPathPrefix(): void
    {
        self::assertSame(
            'https://res.cloudinary.com/demo/image/fetch/w_800/https://example.com/app/hero.jpg',
            new CloudinaryProvider('demo', 'https://example.com/app/')->generateUrl(new ImageTransformation('/hero.jpg', width: 800)),
        );
    }

    #[DataProvider('provideStructuralOperationValues')]
    public function testAnOperationValueThatCloudinaryWouldSplitIsRejected(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The value "%s" of the "e" Cloudinary operation must not contain a "/" or a ",".', $value));

        new CloudinaryProvider('demo', 'https://example.com')->generateUrl(new ImageTransformation('hero.jpg', operations: ['e' => $value]));
    }

    public static function provideStructuralOperationValues(): iterable
    {
        yield 'a slash would start a new transformation component' => ['sharpen/c_scale'];
        yield 'an encoded slash is decoded by Cloudinary first' => ['sharpen%2Fc_scale'];
        yield 'a comma would start a new parameter' => ['sharpen,w_2000'];
        yield 'an encoded comma is decoded by Cloudinary first' => ['sharpen%2cw_2000'];
    }

    public function testItSignsTheUrlTheSameWayAsTheCloudinarySdk(): void
    {
        self::assertSame(
            'https://res.cloudinary.com/test123/image/fetch/s--hH_YcbiS--/http://google.com/path/to/image.png',
            new CloudinaryProvider('test123', 'http://google.com', 'b')->generateUrl(new ImageTransformation('path/to/image.png')),
        );
    }

    public function testItSignsAnUploadUrlTheSameWayAsTheCloudinarySdk(): void
    {
        self::assertSame(
            'https://res.cloudinary.com/test123/image/upload/s--v2fTPYTu--/sample.jpg',
            new CloudinaryProvider('test123', null, 'b')->generateUrl(new ImageTransformation('sample.jpg')),
        );
    }

    public function testTheUploadSignatureCoversTheTransformationAndTheDecodedPublicId(): void
    {
        $provider = new CloudinaryProvider('demo', null, 'secret');

        self::assertSame(
            'https://res.cloudinary.com/demo/image/upload/s--gNRhgbXl--/w_800,h_450,c_fill/uploads/hero.jpg',
            $provider->generateUrl(new ImageTransformation('/uploads/hero.jpg', 800, 450, Fit::Cover)),
        );
        self::assertSame(
            'https://res.cloudinary.com/demo/image/upload/s--UxzL8AXX--/w_800/hero%20image.jpg',
            $provider->generateUrl(new ImageTransformation('hero image.jpg', 800)),
        );
    }

    public function testTheSignatureCoversTheTransformation(): void
    {
        self::assertSame(
            'https://res.cloudinary.com/demo/image/fetch/s---eG9qMD---/w_800,h_450,c_fill/https://example.com/hero.jpg',
            new CloudinaryProvider('demo', 'https://example.com', 'secret')->generateUrl(new ImageTransformation('hero.jpg', 800, 450, Fit::Cover)),
        );
    }

    public function testTheSignatureCoversTheDecodedPath(): void
    {
        self::assertSame(
            'https://res.cloudinary.com/demo/image/fetch/s--23LKKv2O--/w_800/https://example.com/hero%20image.jpg',
            new CloudinaryProvider('demo', 'https://example.com', 'secret')->generateUrl(new ImageTransformation('hero image.jpg', 800)),
        );
    }

    public function testItAdvertisesAutoFormatSupport(): void
    {
        self::assertTrue(new CloudinaryProvider('demo', 'https://example.com')->supportsAutoFormat());
    }

    public function testItAdvertisesItsSupportedOperations(): void
    {
        self::assertSame(
            ['a', 'b', 'bo', 'co', 'd', 'dpr', 'e', 'fl', 'g', 'o', 'r', 't', 'x', 'y', 'z'],
            new CloudinaryProvider('demo', 'https://example.com')->getSupportedOperations(),
        );
    }

    public function testItAdvertisesItsSupportedFormats(): void
    {
        self::assertSame(['avif', 'webp', 'jpeg', 'png'], new CloudinaryProvider('demo', 'https://example.com')->getSupportedFormats());
    }

    public function testItAdvertisesItsName(): void
    {
        self::assertSame('cloudinary', new CloudinaryProvider('demo', 'https://example.com')->getName());
    }

    public function testTheFactoryRejectsAnotherScheme(): void
    {
        self::assertFalse(new CloudinaryProviderFactory()->supports(new Dsn('cloudflare://cdn.example.com')));
    }

    public function testTheFactoryAcceptsItsOwnScheme(): void
    {
        self::assertTrue(new CloudinaryProviderFactory()->supports(new Dsn('cloudinary://demo?origin=https://example.com')));
    }

    public function testTheFactoryRequiresACloudName(): void
    {
        $this->expectException(IncompleteDsnException::class);
        $this->expectExceptionMessage('The Cloudinary image provider requires a cloud name, e.g. "cloudinary://my-cloud".');

        new CloudinaryProviderFactory()->create(new Dsn('cloudinary:?origin=https://example.com'));
    }

    public function testTheFactoryCreatesAnUploadProviderWithoutAnOrigin(): void
    {
        self::assertSame(
            'https://res.cloudinary.com/demo/image/upload/w_800/hero.jpg',
            new CloudinaryProviderFactory()->create(new Dsn('cloudinary://demo'))->generateUrl(new ImageTransformation('hero.jpg', width: 800)),
        );
    }

    public function testTheFactoryPassesTheSecretOnWithoutAnOrigin(): void
    {
        self::assertSame(
            'https://res.cloudinary.com/test123/image/upload/s--v2fTPYTu--/sample.jpg',
            new CloudinaryProviderFactory()->create(new Dsn('cloudinary://test123?api_secret=b'))->generateUrl(new ImageTransformation('sample.jpg')),
        );
    }

    #[DataProvider('provideInvalidOrigins')]
    public function testTheFactoryRejectsAnOriginThatIsNotAnAbsoluteHttpUrl(string $origin): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The option "origin" with value');

        new CloudinaryProviderFactory()->create(new Dsn('cloudinary://demo?origin='.rawurlencode($origin)));
    }

    public static function provideInvalidOrigins(): iterable
    {
        yield 'no scheme' => ['example.com'];
        yield 'not http' => ['ftp://example.com'];
        yield 'no host' => ['https:///uploads'];
        yield 'a query string' => ['https://example.com?a=1'];
        yield 'a fragment' => ['https://example.com#a'];
    }

    public function testTheFactoryCreatesAConfiguredProvider(): void
    {
        $provider = new CloudinaryProviderFactory()->create(new Dsn('cloudinary://demo?origin=https://example.com'));

        self::assertInstanceOf(CloudinaryProvider::class, $provider);
        self::assertSame(
            'https://res.cloudinary.com/demo/image/fetch/w_800/https://example.com/hero.jpg',
            $provider->generateUrl(new ImageTransformation('hero.jpg', width: 800)),
        );
    }

    public function testTheFactoryPassesTheSecretOn(): void
    {
        $provider = new CloudinaryProviderFactory()->create(new Dsn('cloudinary://test123?origin=http://google.com&api_secret=b'));

        self::assertSame(
            'https://res.cloudinary.com/test123/image/fetch/s--hH_YcbiS--/http://google.com/path/to/image.png',
            $provider->generateUrl(new ImageTransformation('path/to/image.png')),
        );
    }

    public function testTheFactoryRejectsAnEmptyApiSecret(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The option "api_secret" with value "" is invalid.');

        new CloudinaryProviderFactory()->create(new Dsn('cloudinary://demo?origin=https://example.com&api_secret='));
    }

    public function testTheFactoryRejectsAnUnknownOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The option "driver" does not exist. Defined options are: "api_secret", "origin".');

        new CloudinaryProviderFactory()->create(new Dsn('cloudinary://demo?origin=https://example.com&driver=imagick'));
    }
}
