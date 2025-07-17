<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Icons\Icon;

final class IconTest extends TestCase
{
    public function testConstructor()
    {
        $icon = new Icon('foo', ['foo' => 'bar']);
        $this->assertSame('foo', $icon->getInnerSvg());
        $this->assertSame('bar', $icon->getAttributes()['foo']);
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

    /**
     * @dataProvider provideRenderAttributesTestCases
     */
    public function testRenderAttributes(array $attributes, string $expected): void
    {
        $icon = new Icon('', $attributes);
        $this->assertStringStartsWith($expected, $icon->toHtml());
    }

    /**
     * @dataProvider provideWithAttributesTestCases
     */
    public function testWithAttributes(array $attributes, array $withAttributes, array $expected): void
    {
        $icon = new Icon('foo', $attributes);
        $icon = $icon->withAttributes($withAttributes);
        $this->assertSame($expected, $icon->getAttributes());
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

    public static function provideRenderAttributesTestCases(): iterable
    {
        yield 'it_renders_empty_attributes' => [
            [],
            '<svg>',
        ];
        yield 'it_renders_one_attribute' => [
            ['foo' => 'bar'],
            '<svg foo="bar">',
        ];
        yield 'it_renders_multiple_attributes' => [
            ['foo' => 'bar', 'baz' => 'qux'],
            '<svg foo="bar" baz="qux">',
        ];
        yield 'it_renders_true_attribute_with_no_value' => [
            ['foo' => true],
            '<svg foo>',
        ];
        yield 'it_does_not_render_attributes_with_false_value' => [
            ['foo' => false, 'aria-foo' => false],
            '<svg>',
        ];
        yield 'it_converts_aria_attribute_with_true_value' => [
            ['aria-bar' => true],
            '<svg aria-bar="true">',
        ];
        yield 'it_does_not_convert_aria_attribute_with_string_value' => [
            ['aria-foo' => '0', 'aria-bar' => 'true', 'aria-baz' => 'false'],
            '<svg aria-foo="0" aria-bar="true" aria-baz="false">',
        ];
    }

    public static function provideWithAttributesTestCases(): iterable
    {
        yield 'it_does_nothing_with_empty_attributes' => [
            ['foo' => 'bar'],
            [],
            ['foo' => 'bar'],
        ];
        yield 'it_does_nothing_with_same_attributes' => [
            ['foo' => 'bar'],
            ['foo' => 'bar'],
            ['foo' => 'bar'],
        ];
        yield 'it_does_nothing_with_same_attributes_incomplete' => [
            ['foo' => 'bar', 'baz' => 'qux'],
            ['foo' => 'bar'],
            ['foo' => 'bar', 'baz' => 'qux'],
        ];
        yield 'it_replaces_value_with_same_key' => [
            ['foo' => 'bar'],
            ['foo' => 'foobar'],
            ['foo' => 'foobar'],
        ];
        yield 'it_replaces_value_with_same_key_and_keep_others' => [
            ['foo' => 'bar', 'baz' => 'qux'],
            ['foo' => 'foobar'],
            ['foo' => 'foobar', 'baz' => 'qux'],
        ];
    }

    public function testSerialize(): void
    {
        $icon = new Icon('foo', ['bar' => 'baz']);

        $this->assertEquals($icon, unserialize(serialize($icon)));
    }
}
