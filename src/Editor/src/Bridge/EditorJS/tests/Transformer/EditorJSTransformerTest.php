<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\EditorJS\Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\EditorJS\Transformer\EditorJSTransformer;
use Symfony\UX\Editor\Content\BlockContent;
use Symfony\UX\Editor\Form\DataTransformer\StorageShape;

final class EditorJSTransformerTest extends TestCase
{
    public function testMetadata()
    {
        $t = new EditorJSTransformer();
        self::assertSame('editorjs', $t->getBridgeId());
        self::assertSame(BlockContent::class, $t->getContentClass());
        self::assertSame(StorageShape::Json, $t->getStorageShape());
    }

    public function testReverseStampsBridgeId()
    {
        $bc = new EditorJSTransformer()->reverseTransform(['version' => '2.30.0', 'blocks' => [['type' => 'paragraph', 'data' => ['text' => 'hi']]]]);
        self::assertInstanceOf(BlockContent::class, $bc);
        self::assertSame('editorjs', $bc->getMetadata()['bridgeId']);
        self::assertSame('2.30.0', $bc->schemaVersion);
    }
}
