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
use Symfony\UX\Editor\Content\HtmlContent;
use Symfony\UX\Editor\Doctrine\EditorContentHtmlType;

final class EditorContentHtmlTypeTest extends TestCase
{
    protected function setUp(): void
    {
        if (!Type::hasType('editor_html')) {
            Type::addType('editor_html', EditorContentHtmlType::class);
        }
    }

    public function testConvertToDatabaseValue(): void
    {
        $t = Type::getType('editor_html');
        $p = $this->createMock(AbstractPlatform::class);
        self::assertSame('<p>hi</p>', $t->convertToDatabaseValue(new HtmlContent('<p>hi</p>'), $p));
        self::assertNull($t->convertToDatabaseValue(null, $p));
    }

    public function testConvertToPHPValue(): void
    {
        $t = Type::getType('editor_html');
        $p = $this->createMock(AbstractPlatform::class);
        $php = $t->convertToPHPValue('<p>hi</p>', $p);
        self::assertInstanceOf(HtmlContent::class, $php);
        self::assertSame('<p>hi</p>', $php->html);
        self::assertNull($t->convertToPHPValue(null, $p));
    }

    public function testTypeName(): void
    {
        self::assertSame('editor_html', Type::getType('editor_html')->getName());
    }
}
