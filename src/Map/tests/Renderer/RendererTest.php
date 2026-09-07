<?php

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
use Symfony\UX\Map\Exception\UnsupportedSchemeException;
use Symfony\UX\Map\Renderer\Renderer;
use Symfony\UX\Map\Renderer\RendererFactoryInterface;
use Symfony\UX\Map\Renderer\RendererInterface;

final class RendererTest extends TestCase
{
    public function testUnsupportedSchemeException(): void
    {
        self::expectException(UnsupportedSchemeException::class);
        self::expectExceptionMessage('The renderer "scheme" is not supported.');

        $renderer = new Renderer([]);
        $renderer->fromString('scheme://default');
    }

    public function testSupportedFactory(): void
    {
        $renderer = new Renderer([
            'one' => $oneFactory = self::createMock(RendererFactoryInterface::class),
            'two' => $twoFactory = self::createMock(RendererFactoryInterface::class),
        ]);

        $oneFactory->expects(self::once())->method('supports')->willReturn(false);
        $twoFactory->expects(self::once())->method('supports')->willReturn(true);
        $twoFactory->expects(self::once())->method('create')->willReturn($twoRenderer = self::createMock(RendererInterface::class));

        $renderer = $renderer->fromString('scheme://default');

        self::assertSame($twoRenderer, $renderer);
    }
}
