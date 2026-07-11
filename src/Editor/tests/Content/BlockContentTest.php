<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Content;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Content\BlockContent;
use Symfony\UX\Editor\Content\EditorContentFormat;

final class BlockContentTest extends TestCase
{
    public function testFormat()
    {
        self::assertSame(EditorContentFormat::Blocks, new BlockContent([])->getFormat());
    }

    public function testRawAndSchemaVersionDefault()
    {
        $bc = new BlockContent([['type' => 'paragraph', 'data' => ['text' => 'hi']]]);
        self::assertSame([['type' => 'paragraph', 'data' => ['text' => 'hi']]], $bc->getRaw());
        self::assertSame('1.0', $bc->schemaVersion);
    }

    public function testIsEmpty()
    {
        self::assertTrue(new BlockContent([])->isEmpty());
        self::assertFalse(new BlockContent([['type' => 'p', 'data' => []]])->isEmpty());
    }

    public function testFilterByType()
    {
        $bc = new BlockContent([
            ['type' => 'header', 'data' => ['text' => 'H']],
            ['type' => 'paragraph', 'data' => ['text' => 'P']],
            ['type' => 'header', 'data' => ['text' => 'H2']],
        ]);
        self::assertCount(2, $bc->filterByType('header')->blocks);
    }

    public function testFromArrayFactory()
    {
        $bc = BlockContent::fromArray(['version' => '2.0', 'blocks' => [['type' => 'p', 'data' => []]]]);
        self::assertSame('2.0', $bc->schemaVersion);
        self::assertCount(1, $bc->blocks);
    }
}
