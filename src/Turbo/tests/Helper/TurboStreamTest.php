<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Tests\Helper;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Turbo\Helper\TurboStream;

class TurboStreamTest extends TestCase
{
    /**
     * @testWith ["append"]
     *           ["prepend"]
     *           ["replace"]
     *           ["update"]
     *           ["before"]
     *           ["after"]
     */
    public function testStream(string $action)
    {
        $this->assertSame(<<<EOHTML
            <turbo-stream action="{$action}" targets="some[&quot;selector&quot;]">
                <template><div>content</div></template>
            </turbo-stream>
            EOHTML,
            TurboStream::$action('some["selector"]', '<div>content</div>')
        );
    }

    /**
     * @testWith ["replace"]
     *           ["update"]
     */
    public function testStreamMorph(string $action)
    {
        $this->assertSame(<<<EOHTML
            <turbo-stream action="{$action}" targets="some[&quot;selector&quot;]" method="morph">
                <template><div>content</div></template>
            </turbo-stream>
            EOHTML,
            TurboStream::$action('some["selector"]', '<div>content</div>', morph: true)
        );
    }

    public function testRemove()
    {
        $this->assertSame(<<<EOHTML
            <turbo-stream action="remove" targets="some[&quot;selector&quot;]"></turbo-stream>
            EOHTML,
            TurboStream::remove('some["selector"]')
        );
    }

    public function testRefreshWithoutId()
    {
        $this->assertSame(<<<EOHTML
            <turbo-stream action="refresh"></turbo-stream>
            EOHTML,
            TurboStream::refresh()
        );
    }

    public function testRefreshWithId()
    {
        $this->assertSame(<<<EOHTML
            <turbo-stream action="refresh" request-id="a&quot;b"></turbo-stream>
            EOHTML,
            TurboStream::refresh('a"b')
        );
    }

    public function testCustom()
    {
        $this->assertSame(<<<EOHTML
            <turbo-stream action="customAction" targets="some[&quot;selector&quot;]" someAttr="someValue" boolAttr intAttr="0" floatAttr="3.14">
                <template><div>content</div></template>
            </turbo-stream>
            EOHTML,
            TurboStream::action('customAction', 'some["selector"]', '<div>content</div>', ['someAttr' => 'someValue', 'boolAttr' => null, 'intAttr' => 0, 'floatAttr' => 3.14])
        );
    }

    /**
     * @dataProvider customThrowsExceptionDataProvider
     *
     * @param array<string, string|int|float|null> $attr
     */
    public function testCustomThrowsException(string $action, string $target, string $html, array $attr)
    {
        $this->expectException(\InvalidArgumentException::class);
        TurboStream::action($action, $target, $html, $attr);
    }

    public static function customThrowsExceptionDataProvider(): \Generator
    {
        yield ['customAction', 'some["selector"]', '<div>content</div>', ['action' => 'someAction']];
        yield ['customAction', 'some["selector"]', '<div>content</div>', ['targets' => 'someTargets']];
    }

    public function testCustomWithCommonAttributeNames()
    {
        $html = TurboStream::action('customAction', 'some["selector"]', '<div>content</div>', ['data-foo' => 'a', 'aria-hidden' => 'true', 'x:y' => 'b', '@click' => 'c', 'a.b_c' => null]);

        $this->assertSame(<<<EOHTML
            <turbo-stream action="customAction" targets="some[&quot;selector&quot;]" data-foo="a" aria-hidden="true" x:y="b" @click="c" a.b_c>
                <template><div>content</div></template>
            </turbo-stream>
            EOHTML,
            $html
        );
    }

    /**
     * @dataProvider customThrowsExceptionOnInvalidAttributeNameDataProvider
     */
    public function testCustomThrowsExceptionOnInvalidAttributeName(string $name)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a valid HTML attribute name');

        TurboStream::action('customAction', 'some["selector"]', '<div>content</div>', [$name => 'value']);
    }

    public static function customThrowsExceptionOnInvalidAttributeNameDataProvider(): \Generator
    {
        yield 'space and event handler' => ['foo onmouseover=alert(1)'];
        yield 'equal sign' => ['a=b c'];
        yield 'empty' => [''];
        yield 'quote' => ['a"b'];
        yield 'greater than' => ['a>b'];
        yield 'slash' => ['a/b'];
        yield 'newline' => ["a\nb"];
        yield 'trailing newline' => ["ab\n"];
        yield 'leading digit' => ['1a'];
        yield 'leading dash' => ['-a'];
    }
}
