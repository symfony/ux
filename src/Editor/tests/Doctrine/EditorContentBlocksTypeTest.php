<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Doctrine;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Content\BlockContent;
use Symfony\UX\Editor\Doctrine\EditorContentBlocksType;
use Symfony\UX\Editor\Exception\ContentSchemaException;

final class EditorContentBlocksTypeTest extends TestCase
{
    protected function setUp(): void
    {
        if (!Type::hasType('editor_blocks')) {
            Type::addType('editor_blocks', EditorContentBlocksType::class);
        }
    }

    public function testRoundTrip(): void
    {
        $t = Type::getType('editor_blocks');
        $p = new SqlitePlatform();
        $bc = new BlockContent([['type' => 'p', 'data' => ['text' => 'x']]], '2.0');
        $json = $t->convertToDatabaseValue($bc, $p);
        self::assertJson($json);
        $back = $t->convertToPHPValue($json, $p);
        self::assertInstanceOf(BlockContent::class, $back);
        self::assertSame('2.0', $back->schemaVersion);
        self::assertSame([['type' => 'p', 'data' => ['text' => 'x']]], $back->blocks);
    }

    public function testNullRoundTrip(): void
    {
        $t = Type::getType('editor_blocks');
        $p = new SqlitePlatform();
        self::assertNull($t->convertToDatabaseValue(null, $p));
        self::assertNull($t->convertToPHPValue(null, $p));
    }

    public function testMalformedJsonThrows(): void
    {
        $this->expectException(ContentSchemaException::class);
        Type::getType('editor_blocks')->convertToPHPValue('{not json', new SqlitePlatform());
    }
}
