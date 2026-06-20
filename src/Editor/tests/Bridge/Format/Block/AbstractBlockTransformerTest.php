<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Bridge\Format\Block;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\Format\Block\AbstractBlockTransformer;
use Symfony\UX\Editor\Content\BlockContent;
use Symfony\UX\Editor\Exception\ContentSchemaException;
use Symfony\UX\Editor\Form\DataTransformer\StorageShape;

final class AbstractBlockTransformerTest extends TestCase
{
    public function testStorageShapeIsJson(): void
    {
        self::assertSame(StorageShape::Json, $this->fake()->getStorageShape());
        self::assertSame(BlockContent::class, $this->fake()->getContentClass());
    }

    public function testRoundTripFromArray(): void
    {
        $t = $this->fake();
        $bc = new BlockContent([['type' => 'p', 'data' => ['text' => 'hi']]], '2.0');
        $arr = $t->transform($bc);
        self::assertSame('2.0', $arr['version']);
        self::assertSame([['type' => 'p', 'data' => ['text' => 'hi']]], $arr['blocks']);

        $back = $t->reverseTransform($arr);
        self::assertInstanceOf(BlockContent::class, $back);
        self::assertSame('2.0', $back->schemaVersion);
        self::assertSame('fakeblock', $back->getMetadata()['bridgeId']);
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
        $this->fake()->reverseTransform(['blocks' => 'not-an-array']);
    }

    private function fake(): AbstractBlockTransformer
    {
        return new class extends AbstractBlockTransformer {
            public function getBridgeId(): string
            {
                return 'fakeblock';
            }
        };
    }
}
