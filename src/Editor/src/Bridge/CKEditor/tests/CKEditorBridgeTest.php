<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\CKEditor\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\CKEditor\CKEditorBridge;
use Symfony\UX\Editor\Bridge\CKEditor\Config\CKEditorConfig;
use Symfony\UX\Editor\Bridge\CKEditor\Transformer\CKEditorTransformer;
use Symfony\UX\Editor\Bridge\Format\Wysiwyg\WysiwygCapabilities;

final class CKEditorBridgeTest extends TestCase
{
    public function testMetadata(): void
    {
        $b = new CKEditorBridge();
        self::assertSame('ckeditor', $b->getId());
        self::assertSame('symfony--ux-editor--ckeditor', $b->getControllerName());
        self::assertEquals(WysiwygCapabilities::default(), $b->getCapabilities());
        self::assertInstanceOf(CKEditorConfig::class, $b->getDefaultConfig());
        self::assertInstanceOf(CKEditorTransformer::class, $b->createTransformer());
    }
}
