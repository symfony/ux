<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Icons\IconRenderer;
use Symfony\UX\Icons\IconRendererInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class IconRendererTest extends KernelTestCase
{
    public function testIconRenderService()
    {
        $this->assertTrue(self::getContainer()->has(IconRendererInterface::class));
    }

    public function testIconRendererAlias()
    {
        $renderer = self::getContainer()->get(IconRendererInterface::class);
        $this->assertInstanceOf(IconRenderer::class, $renderer);
    }

    public function testIconRendererIsPrivate()
    {
        $this->assertFalse(self::getContainer()->has(IconRenderer::class));
    }
}
