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
    public function testStream(string $action): void
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
    public function testStreamMorph(string $action): void
    {
        $this->assertSame(<<<EOHTML
            <turbo-stream action="{$action}" targets="some[&quot;selector&quot;]" method="morph">
                <template><div>content</div></template>
            </turbo-stream>
            EOHTML,
            TurboStream::$action('some["selector"]', '<div>content</div>', morph: true)
        );
    }

    public function testRemove(): void
    {
        $this->assertSame(<<<EOHTML
            <turbo-stream action="remove" targets="some[&quot;selector&quot;]"></turbo-stream>
            EOHTML,
            TurboStream::remove('some["selector"]')
        );
    }

    public function testRefreshWithoutId(): void
    {
        $this->assertSame(<<<EOHTML
            <turbo-stream action="refresh"></turbo-stream>
            EOHTML,
            TurboStream::refresh()
        );
    }

    public function testRefreshWithId(): void
    {
        $this->assertSame(<<<EOHTML
            <turbo-stream action="refresh" request-id="a&quot;b"></turbo-stream>
            EOHTML,
            TurboStream::refresh('a"b')
        );
    }

    public function testCustom(): void
    {
        $this->assertSame(<<<EOHTML
            <turbo-stream action="customAction" targets="some[&quot;selector&quot;]" someAttr="someValue" boolAttr>
                <template><div>content</div></template>
            </turbo-stream>
            EOHTML,
            TurboStream::custom('customAction', 'some["selector"]', '<div>content</div>', ['someAttr="someValue"', 'boolAttr'])
        );
    }
}
