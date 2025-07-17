<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Tests\Renderer;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Map\Exception\LogicException;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Renderer\NullRenderer;
use Symfony\UX\Map\Renderer\RendererInterface;

final class NullRendererTest extends TestCase
{
    public function provideTestRenderMap(): iterable
    {
        yield 'no bridges' => [
            'expected_exception_message' => 'You must install at least one bridge package to use the Symfony UX Map component.',
            'renderer' => new NullRenderer(),
        ];

        yield 'one bridge' => [
            'expected_exception_message' => 'You must install at least one bridge package to use the Symfony UX Map component.'
                .\PHP_EOL.'Try running "composer require symfony/ux-leaflet-map".',
            'renderer' => new NullRenderer(['symfony/ux-leaflet-map']),
        ];

        yield 'two bridges' => [
            'expected_exception_message' => 'You must install at least one bridge package to use the Symfony UX Map component.'
                .\PHP_EOL.'Try running "composer require symfony/ux-leaflet-map" or "composer require symfony/ux-google-map".',
            'renderer' => new NullRenderer(['symfony/ux-leaflet-map', 'symfony/ux-google-map']),
        ];
    }

    /**
     * @dataProvider provideTestRenderMap
     */
    public function testRenderMap(string $expectedExceptionMessage, RendererInterface $renderer): void
    {
        self::expectException(LogicException::class);
        self::expectExceptionMessage($expectedExceptionMessage);

        $renderer->renderMap(new Map(), []);
    }
}
