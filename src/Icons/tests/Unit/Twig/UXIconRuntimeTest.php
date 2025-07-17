<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\IconRendererInterface;
use Symfony\UX\Icons\Twig\UXIconRuntime;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class UXIconRuntimeTest extends TestCase
{
    public function testRenderIconIgnoreNotFound(): void
    {
        $renderer = $this->createMock(IconRendererInterface::class);
        $renderer->method('renderIcon')
            ->willThrowException(new IconNotFoundException('Icon "foo" not found.'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('Icon "foo" not found.');

        $runtime = new UXIconRuntime($renderer, true, $logger);
        $this->assertEquals('', $runtime->renderIcon('not_found'));

        $runtime = new UXIconRuntime($renderer, false);
        $this->expectException(IconNotFoundException::class);
        $runtime->renderIcon('not_found');
    }
}
