<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Imagor\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\UX\Image\Bridge\Imagor\ImagorProvider;
use Symfony\UX\Image\Bridge\Imagor\ImagorProviderFactory;
use Symfony\UX\Image\Exception\IncompleteDsnException;
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\ImageUrlGenerator;
use Symfony\UX\Image\Provider\Dsn;
use Symfony\UX\Image\UXImageBundle;

final class ImagorProviderTest extends TestCase
{
    #[DataProvider('provideUrls')]
    public function testUrls(ImageTransformation $transformation, string $path): void
    {
        $provider = new ImagorProvider('https://images.example.com');

        self::assertSame('https://images.example.com/unsafe/'.$path, $provider->generateUrl($transformation));
    }

    public static function provideUrls(): iterable
    {
        yield 'no options' => [
            new ImageTransformation('hero.jpg'),
            'hero.jpg',
        ];
        yield 'width' => [
            new ImageTransformation('hero.jpg', width: 800),
            '800x0/hero.jpg',
        ];
        yield 'height' => [
            new ImageTransformation('hero.jpg', height: 450),
            '0x450/hero.jpg',
        ];
        yield 'cover' => [
            new ImageTransformation('hero.jpg', 800, 450, Fit::Cover, 'webp', 80),
            '800x450/filters:quality(80):format(webp)/hero.jpg',
        ];
        yield 'contain' => [
            new ImageTransformation('hero.jpg', 800, 450, Fit::Contain),
            'fit-in/800x450/hero.jpg',
        ];
        yield 'jpeg' => [
            new ImageTransformation('hero.jpg', format: 'jpeg'),
            'filters:format(jpeg)/hero.jpg',
        ];
        yield 'storage key' => [
            new ImageTransformation('/bucket/hero image.jpg'),
            'bucket%2Fhero%20image.jpg',
        ];
        yield 'source query' => [
            new ImageTransformation('https://origin.test/a%20b.jpg?v=1&size=2#page'),
            'https%3A%2F%2Forigin.test%2Fa%2520b.jpg%3Fv%3D1%26size%3D2%23page',
        ];
        yield 'source cannot become operations' => [
            new ImageTransformation('meta/filters:grayscale()/a.jpg'),
            'meta%2Ffilters%3Agrayscale%28%29%2Fa.jpg',
        ];
        yield 'special filename' => [
            new ImageTransformation('café+100%.jpg'),
            'caf%C3%A9%2B100%25.jpg',
        ];
        yield 'ordered filters' => [
            new ImageTransformation('hero.jpg', operations: ['rotate' => 90, 'crop' => '10,20,30,40', 'grayscale' => true, 'strip_exif' => false]),
            'filters:rotate(90):crop(10,20,30,40):grayscale()/hero.jpg',
        ];
        yield 'zero is not omitted' => [
            new ImageTransformation('hero.jpg', operations: ['brightness' => 0]),
            'filters:brightness(0)/hero.jpg',
        ];
        yield 'focal region' => [
            new ImageTransformation('hero.jpg', operations: ['focal' => '10x20:30x40']),
            'filters:focal(10x20:30x40)/hero.jpg',
        ];
    }

    public function testOriginEncodesFilenameBeforeEncodingTheSource(): void
    {
        $provider = new ImagorProvider('https://images.example.com/proxy/', origin: 'https://origin.test/media/');

        $url = $provider->generateUrl(new ImageTransformation('/a b?#%.jpg'));

        self::assertSame('https://images.example.com/proxy/unsafe/https%3A%2F%2Forigin.test%2Fmedia%2Fa%2520b%253F%2523%2525.jpg', $url);
    }

    public function testAnAbsoluteSourceStaysUnderTheOrigin(): void
    {
        $provider = new ImagorProvider('https://images.example.com', 'secret', 'https://origin.test');

        $url = $provider->generateUrl(new ImageTransformation('http://169.254.169.254/latest/meta-data/'));

        self::assertStringEndsWith('/https%3A%2F%2Forigin.test%2Fhttp%253A%2F%2F169.254.169.254%2Flatest%2Fmeta-data%2F', $url);
    }

    #[DataProvider('provideSignatures')]
    public function testStockImagorSignatures(string $signer, int $truncate, string $signature): void
    {
        // Generated independently with imagorpath.NewHMACSigner from Imagor v1.9.13.
        $provider = new ImagorProvider('https://images.example.com/proxy', 'secret', signer: $signer, signTruncate: $truncate);
        $transformation = new ImageTransformation('https://origin.test/a.jpg?v=1', 800, 450, Fit::Cover, 'webp', 80);

        $url = $provider->generateUrl($transformation);

        self::assertSame('https://images.example.com/proxy/'.$signature.'/800x450/filters:quality(80):format(webp)/https%3A%2F%2Forigin.test%2Fa.jpg%3Fv%3D1', $url);
    }

    public static function provideSignatures(): iterable
    {
        yield ['sha1', 0, 'xqIjQjjuAWi5TgqFIYtYNYceflA='];
        yield ['sha256', 40, 'mqayiXbd1wwtfx_fD-JxRqtFSMVIy7fO_vk6eoAh'];
        yield ['sha512', 0, '34NIUztGQsU_3ezmBC-75zlD7lOIP-Gw2Wk1Fcnv0TIQOktkJ3S_wiCUajVjht4G9x5LJqyQCjOG36yq5YEp_g=='];
    }

    public function testFactory(): void
    {
        $factory = new ImagorProviderFactory();

        $provider = $factory->create(new Dsn('imagor://localhost:8000/proxy?secure=false&origin=https%3A%2F%2Forigin.test%2Fmedia'));

        self::assertTrue($factory->supports(new Dsn('imagor://localhost')));
        self::assertFalse($factory->supports(new Dsn('imgix://localhost')));
        self::assertSame('http://localhost:8000/proxy/unsafe/https%3A%2F%2Forigin.test%2Fmedia%2Fhero.jpg', $provider->generateUrl(new ImageTransformation('hero.jpg')));
        self::assertSame('imagor', $provider->getName());
        self::assertFalse($provider->supportsAutoFormat());
        self::assertSame(['avif', 'webp', 'jpeg', 'png'], $provider->getSupportedFormats());
    }

    public function testBundleRegistersAndResolvesTheProvider(): void
    {
        $container = new ContainerBuilder(new ParameterBag(['kernel.environment' => 'test', 'kernel.build_dir' => __DIR__]));
        $bundle = new UXImageBundle();
        $bundle->build($container);
        $bundle->getContainerExtension()->load([['provider' => 'imagor://images.example.com']], $container);
        $container->getDefinition('ux_image.provider')->setPublic(true);

        $factoryTags = $container->getDefinition('ux_image.provider_factory.imagor')->getTag('ux_image.provider_factory');
        $container->compile();
        $provider = $container->get('ux_image.provider');

        self::assertSame([['provider' => 'imagor']], $factoryTags);
        self::assertInstanceOf(ImagorProvider::class, $provider);
        self::assertSame('https://images.example.com/unsafe/hero.jpg', $provider->generateUrl(new ImageTransformation('hero.jpg')));
    }

    public function testFactorySigningOptions(): void
    {
        $expected = new ImagorProvider('https://images.example.com', 'secret', signer: 'sha256', signTruncate: 40);

        $provider = new ImagorProviderFactory()->create(new Dsn('imagor://images.example.com?sign_key=secret&signer=sha256&sign_truncate=40'));

        $transformation = new ImageTransformation('hero.jpg');
        self::assertSame($expected->generateUrl($transformation), $provider->generateUrl($transformation));
    }

    public function testMissingHost(): void
    {
        $this->expectException(IncompleteDsnException::class);

        new ImagorProviderFactory()->create(new Dsn('imagor:'));
    }

    #[DataProvider('provideInvalidDsns')]
    public function testInvalidDsn(string $suffix): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ImagorProviderFactory()->create(new Dsn('imagor://localhost'.$suffix));
    }

    public static function provideInvalidDsns(): iterable
    {
        $suffixes = [
            '?unknown=1',
            '?sign_key=',
            '?sign_key[]=x',
            '?signer=md5',
            '?sign_truncate=16',
            '?sign_truncate=89',
            '?sign_truncate=-1',
            '?sign_truncate=abc',
            '?secure=maybe',
            '?origin=relative',
            '?origin=https://example.com?x=1',
        ];

        foreach ($suffixes as $suffix) {
            yield $suffix => [$suffix];
        }
    }

    #[DataProvider('provideInvalidOperations')]
    public function testInvalidOperations(array $operations): void
    {
        $provider = new ImagorProvider('https://images.example.com');

        $this->expectException(InvalidArgumentException::class);

        $provider->generateUrl(new ImageTransformation('hero.jpg', operations: $operations));
    }

    public static function provideInvalidOperations(): iterable
    {
        yield 'a flag takes a boolean' => [['grayscale' => 'false']];
        yield 'an argument cannot close the filter' => [['blur' => '1):format(png']];
        yield 'an argument cannot add a path segment' => [['fill' => 'red/other.jpg']];
        yield 'an argument must be a scalar' => [['blur' => []]];
        yield 'an argument must be a number or a string' => [['blur' => true]];
    }

    #[DataProvider('provideUnsupportedOperations')]
    public function testUrlGeneratorRejectsUnsupportedOperations(string $name): void
    {
        $generator = new ImageUrlGenerator(new ImagorProvider('https://images.example.com'), ['imagor']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The image operation "%s" is not supported by the "imagor" provider', $name));

        $generator->generate('hero.jpg', operations: ['imagor' => [$name => true]]);
    }

    public static function provideUnsupportedOperations(): iterable
    {
        yield 'an unknown filter' => ['ocr'];
        yield 'the meta endpoint' => ['meta'];
        yield 'the common format' => ['format'];
        yield 'the common quality' => ['quality'];
    }

    public function testUrlGeneratorDefaultsToCoverAndRejectsAutoFormat(): void
    {
        $generator = new ImageUrlGenerator(new ImagorProvider('https://images.example.com'), ['imagor']);

        $url = $generator->generate('hero.jpg', 400, 300, operations: ['imagor' => ['grayscale' => true]]);

        self::assertSame('https://images.example.com/unsafe/400x300/filters:grayscale()/hero.jpg', $url);
        $this->expectException(InvalidArgumentException::class);
        $generator->generate('hero.jpg', format: 'auto');
    }
}
