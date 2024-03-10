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
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\Iconify;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class IconifyTest extends TestCase
{
    public function testFetchIcon(): void
    {
        $iconify = new Iconify(
            cache: new NullAdapter(),
            endpoint: 'https://example.com',
            http: new MockHttpClient([
                new JsonMockResponse([
                    'bi' => [],
                ]),
                new JsonMockResponse([
                    'icons' => [
                        'heart' => [
                            'body' => '<path d="M0 0h24v24H0z" fill="none"/>',
                            'height' => 24,
                        ],
                    ],
                ]),
            ]),
        );

        $icon = $iconify->fetchIcon('bi', 'heart');

        $this->assertEquals($icon->getInnerSvg(), '<path d="M0 0h24v24H0z" fill="none"/>');
        $this->assertEquals($icon->getAttributes(), ['viewBox' => '0 0 24 24']);
    }

    public function testFetchIconThrowsWhenIconSetDoesNotExists(): void
    {
        $iconify = new Iconify(
            cache: new NullAdapter(),
            endpoint: 'https://example.com',
            http: new MockHttpClient([
                new JsonMockResponse([]),
            ]),
        );

        $this->expectException(IconNotFoundException::class);
        $this->expectExceptionMessage('The icon "bi:heart" does not exist on iconify.design.');

        $iconify->fetchIcon('bi', 'heart');
    }

    public function testFetchIconUsesIconsetViewBoxHeight(): void
    {
        $iconify = new Iconify(
            cache: new NullAdapter(),
            endpoint: 'https://example.com',
            http: new MockHttpClient([
                new JsonMockResponse([
                    'bi' => [
                        'height' => 17,
                    ],
                ]),
                new JsonMockResponse([
                    'icons' => [
                        'heart' => [
                            'body' => '<path d="M0 0h24v24H0z" fill="none"/>',
                        ],
                    ],
                ]),
            ]),
        );

        $icon = $iconify->fetchIcon('bi', 'heart');

        $this->assertIsArray($icon->getAttributes());
        $this->assertArrayHasKey('viewBox', $icon->getAttributes());
        $this->assertEquals('0 0 17 17', $icon->getAttributes()['viewBox']);
    }

    public function testFetchIconThrowsWhenViewBoxCannotBeComputed(): void
    {
        $iconify = new Iconify(
            cache: new NullAdapter(),
            endpoint: 'https://example.com',
            http: new MockHttpClient([
                new JsonMockResponse([
                    'bi' => [],
                ]),
                new JsonMockResponse([
                    'icons' => [
                        'heart' => [
                            'body' => '<path d="M0 0h24v24H0z" fill="none"/>',
                        ],
                    ],
                ]),
            ]),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The icon "bi:heart" does not have a width or height.');

        $iconify->fetchIcon('bi', 'heart');
    }
}
