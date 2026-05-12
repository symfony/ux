<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\EditorJS\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\EditorJS\Config\EditorJSConfig;
use Symfony\UX\Editor\Bridge\EditorJS\EditorJSBridge;
use Symfony\UX\Editor\Bridge\EditorJS\Transformer\EditorJSTransformer;
use Symfony\UX\Editor\Bridge\Format\Block\BlockCapabilities;

final class EditorJSBridgeTest extends TestCase
{
    public function testMetadata(): void
    {
        $b = new EditorJSBridge();
        self::assertSame('editorjs', $b->getId());
        self::assertSame('symfony--ux-editor--editorjs', $b->getControllerName());
        self::assertEquals(BlockCapabilities::default(), $b->getCapabilities());
        self::assertInstanceOf(EditorJSConfig::class, $b->getDefaultConfig());
        self::assertInstanceOf(EditorJSTransformer::class, $b->createTransformer());
    }
}
