<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\Iconify;
use Symfony\UX\Icons\Svg\Icon;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class IconifyTest extends TestCase
{
    public function testGetMetadata(): void
    {
        $responseFile = __DIR__.'/../Fixtures/Iconify/collections.json';
        $client = $this->createHttpClient(json_decode(file_get_contents($responseFile)));
        $iconify = new Iconify('https://localhost', $client);

        $metadata = $iconify->metadataFor('fa6-solid');
        $this->assertSame('Font Awesome Solid', $metadata['name']);
    }

    /**
     * @dataProvider provideHttpErrors
     */
    public function testGetMetadataWhenHttpError(int $code, string $expectedException): void
    {
        $mockClient = $this->createHttpClient([], $code);
        $iconify = new Iconify('https://localhost', $mockClient);

        $this->expectException($expectedException);

        $iconify->metadataFor('foo');
    }

    public function testFetchIcon(): void
    {
        $responseFile = __DIR__.'/../Fixtures/Iconify/icon.json';
        $client = new MockHttpClient(MockResponse::fromFile($responseFile));
        $iconify = new Iconify('https://localhost', $client);

        $icon = $iconify->fetchIcon('bi', 'heart');

        $this->assertInstanceOf(Icon::class, $icon);
    }

    /**
     * @dataProvider provideHttpErrors
     */
    public function testFetchIconWhenHttpError(int $code, string $expectedException): void
    {
        $mockClient = $this->createHttpClient([], $code);
        $iconify = new Iconify('https://localhost', $mockClient);

        $this->expectException($expectedException);

        $iconify->fetchIcon('foo', 'bar');
    }

    public function testFetchSvg(): void
    {
        $responseFile = __DIR__.'/../Fixtures/Iconify/icon.svg';
        $client = new MockHttpClient(MockResponse::fromFile($responseFile));
        $iconify = new Iconify('https://localhost', $client);

        $svg = $iconify->fetchSvg('foo', 'bar');

        $this->assertIsString($svg);
        $this->stringContains('-.224l.235-.468ZM6.013 2.06c-.649-.1', $svg);
    }

    /**
     * @dataProvider provideHttpErrors
     */
    public function testFetchSvgWhenHttpError(int $code, string $expectedException): void
    {
        $mockClient = $this->createHttpClient([], $code);
        $iconify = new Iconify('https://localhost', $mockClient);

        $this->expectException($expectedException);
        $iconify->fetchSvg('foo', 'bar');
    }

    public static function provideHttpErrors(): iterable
    {
        yield [400, IconNotFoundException::class];
        yield [404, IconNotFoundException::class];
        yield [500, IconNotFoundException::class];
    }

    private function createHttpClient(mixed $data, int $code = 200): MockHttpClient
    {
        $mockResponse = new JsonMockResponse($data, ['http_code' => $code]);

        return new MockHttpClient($mockResponse);
    }
}
