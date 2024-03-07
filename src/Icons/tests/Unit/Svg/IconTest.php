<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Unit\Svg;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Icons\Svg\Icon;

final class IconTest extends TestCase
{
    public function testConstructor()
    {
        $icon = new Icon('foo', ['foo' => 'bar']);
        $this->assertSame('foo', $icon->getInnerSvg());
        $this->assertSame('bar', $icon['foo']);
    }

    /**
     * @dataProvider provideIdToName
     */
    public function testIdToName(string $id, string $name)
    {
        $this->assertSame($name, Icon::idToName($id));
    }

    /**
     * @dataProvider provideInvalidIds
     */
    public function testIdToNameThrowsException(string $id)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The id "'.$id.'" is not a valid id.');

        Icon::idToName($id);
    }

    /**
     * @dataProvider provideNameToId
     */
    public function testNameToId(string $name, string $id)
    {
        $this->assertEquals($id, Icon::nameToId($name));
    }

    /**
     * @dataProvider provideInvalidNames
     */
    public function testNameToIdThrowsException(string $name)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The name "'.$name.'" is not a valid name.');

        Icon::nameToId($name);
    }

    /**
     * @dataProvider provideValidIds
     */
    public function testIsValidIdWithValidIds(string $id): void
    {
        $this->assertTrue(Icon::isValidId($id));
    }

    /**
     * @dataProvider provideInvalidIds
     */
    public function testIsValidIdWithInvalidIds(string $id): void
    {
        $this->assertFalse(Icon::isValidId($id));
    }

    /**
     * @dataProvider provideValidNames
     */
    public function testIsValidNameWithValidNames(string $name): void
    {
        $this->assertTrue(Icon::isValidName($name));
    }

    /**
     * @dataProvider provideInvalidNames
     */
    public function testIsValidNameWithInvalidNames(string $name): void
    {
        $this->assertFalse(Icon::isValidName($name));
    }

    /**
     * @dataProvider provideInvalidIds
     */
    public function testInvalidIdToName(string $id)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The id "'.$id.'" is not a valid id.');

        $this->assertFalse(Icon::isValidId($id));
        Icon::idToName($id);
    }

    public static function provideIdToName(): iterable
    {
        yield from [
            ['foo', 'foo'],
            ['foo-bar', 'foo-bar'],
            ['foo-bar-baz', 'foo-bar-baz'],
            ['foo--bar', 'foo:bar'],
            ['foo--bar--baz', 'foo:bar:baz'],
            ['foo-bar--baz', 'foo-bar:baz'],
            ['foo--bar-baz', 'foo:bar-baz'],
        ];
    }

    public static function provideNameToId(): iterable
    {
        yield from [
            ['foo', 'foo'],
            ['foo-bar', 'foo-bar'],
            ['foo-bar-baz', 'foo-bar-baz'],
            ['foo:bar', 'foo--bar'],
            ['foo:bar:baz', 'foo--bar--baz'],
            ['foo-bar:baz', 'foo-bar--baz'],
            ['foo:bar-baz', 'foo--bar-baz'],
        ];
    }

    public static function provideValidIds(): iterable
    {
        yield from self::provideValidIdentifiers();
        yield from [
            ['foo--bar'],
            ['foo--bar-baz'],
            ['foo-bar--baz'],
        ];
    }

    public static function provideInvalidIds(): iterable
    {
        yield from self::provideInvalidIdentifiers();
        yield from [
            ['foo:'],
            [':foo'],
            ['foo::bar'],
        ];
    }

    public static function provideValidNames(): iterable
    {
        yield from self::provideValidIdentifiers();
        yield from [
            ['foo:bar-baz'],
            ['foo:bar'],
        ];
    }

    public static function provideInvalidNames(): iterable
    {
        yield from self::provideInvalidIdentifiers();
        yield from [
            ['foo:'],
            [':foo'],
            ['foo::bar'],
            ['foo:::bar'],
            ['foo::'],
            ['::foo'],
            ['foo--bar'],
        ];
    }

    private static function provideValidIdentifiers(): iterable
    {
        yield from [
            ['foo'],
            ['123'],
            ['foo-bar'],
            ['123-456'],
        ];
    }

    private static function provideInvalidIdentifiers(): iterable
    {
        yield from [
            [''],
            ['FOO'],
            ['&'],
            ['é'],
            ['.'],
            ['/'],
            ['foo-'],
            ['-bar'],
            ['_'],
            ['foo_bar'],
            [' '],
            ['foo '],
            [' foo'],
            ['🙂'],
        ];
    }
}
