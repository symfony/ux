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

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Content\PageContent;
use Symfony\UX\Editor\Doctrine\EditorContentPageType;
use Symfony\UX\Editor\Exception\ContentSchemaException;

final class EditorContentPageTypeTest extends TestCase
{
    protected function setUp(): void
    {
        if (!Type::hasType('editor_page')) {
            Type::addType('editor_page', EditorContentPageType::class);
        }
    }

    public function testRoundTrip()
    {
        $t = Type::getType('editor_page');
        $p = $this->createMock(AbstractPlatform::class);
        $pc = new PageContent('<h1>x</h1>', 'h1{}', [['type' => 'image', 'url' => '/x.png']], [['type' => 'h1']]);
        $back = $t->convertToPHPValue($t->convertToDatabaseValue($pc, $p), $p);
        self::assertInstanceOf(PageContent::class, $back);
        self::assertSame('<h1>x</h1>', $back->html);
        self::assertSame('h1{}', $back->css);
        self::assertCount(1, $back->assets);
        self::assertCount(1, $back->components);
    }

    public function testMalformedThrows()
    {
        $this->expectException(ContentSchemaException::class);
        Type::getType('editor_page')->convertToPHPValue('not json', $this->createMock(AbstractPlatform::class));
    }
}
