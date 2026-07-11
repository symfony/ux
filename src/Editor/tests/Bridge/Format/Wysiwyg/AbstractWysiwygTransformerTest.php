<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Bridge\Format\Wysiwyg;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\Format\Wysiwyg\AbstractWysiwygTransformer;
use Symfony\UX\Editor\Content\HtmlContent;
use Symfony\UX\Editor\Form\DataTransformer\StorageShape;

final class AbstractWysiwygTransformerTest extends TestCase
{
    public function testStorageShapeIsScalar()
    {
        $t = $this->fake();
        self::assertSame(StorageShape::Scalar, $t->getStorageShape());
        self::assertSame(HtmlContent::class, $t->getContentClass());
    }

    public function testRoundTrip()
    {
        $t = $this->fake();
        self::assertSame('<p>hi</p>', $t->transform(new HtmlContent('<p>hi</p>')));
        self::assertNull($t->transform(null));
        $back = $t->reverseTransform('<p>hi</p>');
        self::assertInstanceOf(HtmlContent::class, $back);
        self::assertSame('<p>hi</p>', $back->html);
        self::assertNull($t->reverseTransform(null));
        self::assertNull($t->reverseTransform(''));
    }

    public function testMetadataAttachedOnReverse()
    {
        self::assertSame('fake', $this->fake()->reverseTransform('<p>x</p>')->getMetadata()['bridgeId']);
    }

    private function fake(): AbstractWysiwygTransformer
    {
        return new class extends AbstractWysiwygTransformer {
            public function getBridgeId(): string
            {
                return 'fake';
            }
        };
    }
}
