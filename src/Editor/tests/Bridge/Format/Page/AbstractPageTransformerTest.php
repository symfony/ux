<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Bridge\Format\Page;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\Format\Page\AbstractPageTransformer;
use Symfony\UX\Editor\Content\PageContent;
use Symfony\UX\Editor\Exception\ContentSchemaException;
use Symfony\UX\Editor\Form\DataTransformer\StorageShape;

final class AbstractPageTransformerTest extends TestCase
{
    public function testStorageShapeIsJsonByDefault(): void
    {
        $t = $this->fake();
        self::assertSame(StorageShape::Json, $t->getStorageShape());
        self::assertSame(PageContent::class, $t->getContentClass());
    }

    public function testRoundTripBundle(): void
    {
        $t = $this->fake();
        $pc = new PageContent('<h1>x</h1>', 'h1{}', [['type' => 'image', 'url' => '/x.png']], [['type' => 'h1']]);
        $arr = $t->transform($pc);
        self::assertSame('<h1>x</h1>', $arr['html']);
        self::assertSame('h1{}', $arr['css']);
        self::assertCount(1, $arr['assets']);
        self::assertCount(1, $arr['components']);

        $back = $t->reverseTransform($arr);
        self::assertInstanceOf(PageContent::class, $back);
        self::assertSame('<h1>x</h1>', $back->html);
        self::assertSame('fakepage', $back->getMetadata()['bridgeId']);
    }

    public function testNullPaths(): void
    {
        $t = $this->fake();
        self::assertNull($t->transform(null));
        self::assertNull($t->reverseTransform(null));
        self::assertNull($t->reverseTransform([]));
    }

    public function testMalformedThrows(): void
    {
        $this->expectException(ContentSchemaException::class);
        $this->fake()->reverseTransform(['html' => ['not-a-string']]);
    }

    private function fake(): AbstractPageTransformer
    {
        return new class extends AbstractPageTransformer {
            public function getBridgeId(): string
            {
                return 'fakepage';
            }
        };
    }
}
