<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\CKEditor\Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\CKEditor\Transformer\CKEditorTransformer;
use Symfony\UX\Editor\Content\HtmlContent;
use Symfony\UX\Editor\Form\DataTransformer\StorageShape;

final class CKEditorTransformerTest extends TestCase
{
    public function testMetadata(): void
    {
        $t = new CKEditorTransformer();
        self::assertSame('ckeditor', $t->getBridgeId());
        self::assertSame(HtmlContent::class, $t->getContentClass());
        self::assertSame(StorageShape::Scalar, $t->getStorageShape());
    }

    public function testReverseStampsBridgeId(): void
    {
        $hc = new CKEditorTransformer()->reverseTransform('<p>hi</p>');
        self::assertInstanceOf(HtmlContent::class, $hc);
        self::assertSame('<p>hi</p>', $hc->html);
        self::assertSame('ckeditor', $hc->getMetadata()['bridgeId']);
    }
}
