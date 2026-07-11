<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\GrapesJS\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\Format\Page\PageCapabilities;
use Symfony\UX\Editor\Bridge\GrapesJS\Config\GrapesJSConfig;
use Symfony\UX\Editor\Bridge\GrapesJS\GrapesJSBridge;
use Symfony\UX\Editor\Bridge\GrapesJS\Transformer\GrapesJSTransformer;

final class GrapesJSBridgeTest extends TestCase
{
    public function testMetadata()
    {
        $b = new GrapesJSBridge();
        self::assertSame('grapesjs', $b->getId());
        self::assertSame('symfony--ux-editor--grapesjs', $b->getControllerName());
        self::assertEquals(PageCapabilities::default(), $b->getCapabilities());
        self::assertInstanceOf(GrapesJSConfig::class, $b->getDefaultConfig());
        self::assertInstanceOf(GrapesJSTransformer::class, $b->createTransformer());
    }
}
