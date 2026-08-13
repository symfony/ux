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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Icons\Icon;
use Symfony\UX\Icons\IconFactory;

final class IconFactoryTest extends TestCase
{
    /**
     * @dataProvider provideSanitizedBodies
     */
    #[DataProvider('provideSanitizedBodies')]
    public function testFromBodySanitizes(string $body, string $expected)
    {
        $icon = (new IconFactory())->fromBody($body);

        $this->assertInstanceOf(Icon::class, $icon);
        $this->assertSame($expected, $icon->getInnerSvg());
    }

    /**
     * @dataProvider provideSafeBodies
     */
    #[DataProvider('provideSafeBodies')]
    public function testFromBodyKeepsSafeContentVerbatim(string $body)
    {
        // Safe content must be returned byte-for-byte (no lossy re-serialization).
        $this->assertSame($body, (new IconFactory())->fromBody($body)->getInnerSvg());
    }

    /**
     * @dataProvider provideInvalidBodies
     */
    #[DataProvider('provideInvalidBodies')]
    public function testFromBodyThrowsOnInvalidSvg(string $body)
    {
        $this->expectException(\RuntimeException::class);

        (new IconFactory())->fromBody($body);
    }

    public function testFromBodyKeepsGivenAttributes()
    {
        $icon = (new IconFactory())->fromBody('<rect onload="alert(1)"/>', ['viewBox' => '0 0 16 16', 'xmlns' => 'http://www.w3.org/2000/svg']);

        $this->assertSame('<rect></rect>', $icon->getInnerSvg());
        $this->assertSame(['viewBox' => '0 0 16 16', 'xmlns' => 'http://www.w3.org/2000/svg'], $icon->getAttributes());
    }

    public function testFromFileSanitizesMaliciousIcon()
    {
        $icon = (new IconFactory())->fromFile(__DIR__.'/../Fixtures/svg/malicious.svg');

        // <script>, <foreignObject>, <set attributeName="on*"> dropped; on* and javascript: attributes stripped.
        $this->assertSame(
            '<rect fill="red"></rect><a><path d="M0 0"></path></a><circle cx="1" cy="1" r="1"></circle>',
            $icon->getInnerSvg(),
        );
        // The on* attribute on the root <svg> is stripped; namespace declarations are preserved
        // so the icon renders identically to one fetched on-demand from Iconify.
        $this->assertSame(['xmlns' => 'http://www.w3.org/2000/svg', 'xmlns:xlink' => 'http://www.w3.org/1999/xlink', 'viewBox' => '0 0 24 24'], $icon->getAttributes());
    }

    /**
     * @dataProvider provideValidFiles
     */
    #[DataProvider('provideValidFiles')]
    public function testFromFileReadsValidSvg(string $name, array $expectedAttributes, string $expectedContent)
    {
        $icon = (new IconFactory())->fromFile(__DIR__.'/../Fixtures/svg/'.$name.'.svg');

        $this->assertSame($expectedContent, $icon->getInnerSvg());
        $this->assertSame($expectedAttributes, $icon->getAttributes());
    }

    /**
     * @dataProvider provideInvalidFiles
     */
    #[DataProvider('provideInvalidFiles')]
    public function testFromFileThrowsOnInvalidSvg(string $name)
    {
        $this->expectException(\RuntimeException::class);

        (new IconFactory())->fromFile(__DIR__.'/../Fixtures/svg/'.$name.'.svg');
    }

    /**
     * Malicious SVG bodies and their expected sanitized inner SVG.
     */
    public static function provideSanitizedBodies(): iterable
    {
        // Event handler attributes
        yield 'onload' => ['<rect onload="alert(1)" fill="red"/>', '<rect fill="red"></rect>'];
        yield 'onclick' => ['<path onclick="x" d="M0 0"/>', '<path d="M0 0"></path>'];
        yield 'onerror' => ['<image onerror="x" href="#a"/>', '<image href="#a"></image>'];
        yield 'onmouseover (nested)' => ['<g onmouseover="x"><path d="z"/></g>', '<g><path d="z"></path></g>'];
        yield 'onfocusin' => ['<rect onfocusin="x"/>', '<rect></rect>'];
        yield 'ontoggle' => ['<rect ontoggle="x"/>', '<rect></rect>'];
        yield 'uppercase ONLOAD' => ['<rect ONLOAD="x"/>', '<rect></rect>'];
        yield 'mixed-case OnLoad' => ['<rect OnLoad="x" d="m"/>', '<rect d="m"></rect>'];

        // Script-capable elements
        yield 'script' => ['<script>alert(1)</script><path d="y"/>', '<path d="y"></path>'];
        yield 'self-closing script' => ['<script src="x.js"/><path d="y"/>', '<path d="y"></path>'];
        yield 'nested script' => ['<g><defs><script>x</script></defs><path d="z"/></g>', '<g><defs></defs><path d="z"></path></g>'];
        yield 'script with xlink:href' => ['<script xlink:href="https://evil/x.js"/><path d="y"/>', '<path d="y"></path>'];
        yield 'foreignObject' => ['<foreignObject><div onclick="x">hi</div></foreignObject><path d="a"/>', '<path d="a"></path>'];
        yield 'iframe' => ['<iframe src="javascript:alert(1)"></iframe><path d="b"/>', '<path d="b"></path>'];
        yield 'object' => ['<object data="x"></object><path d="b"/>', '<path d="b"></path>'];
        yield 'embed' => ['<embed src="x"/><path d="b"/>', '<path d="b"></path>'];
        yield 'handler' => ['<handler>x</handler><path d="b"/>', '<path d="b"></path>'];

        // SMIL animations installing an event handler
        yield 'smil set onload' => ['<set attributeName="onload" to="x"/><path d="c"/>', '<path d="c"></path>'];
        yield 'smil animate onclick' => ['<animate attributeName="onclick" to="x"/><path d="d"/>', '<path d="d"></path>'];
        yield 'smil animateTransform onbegin' => ['<animateTransform attributeName="onbegin" to="x"/><path d="d"/>', '<path d="d"></path>'];

        // Script-capable URLs
        yield 'href javascript:' => ['<a href="javascript:alert(1)"/>', '<a></a>'];
        yield 'xlink:href javascript:' => ['<a xlink:href="javascript:alert(1)"><path d="e"/></a>', '<a><path d="e"></path></a>'];
        yield 'href javascript: with spaces' => ['<a xlink:href="  javascript:alert(1)"><path d="f"/></a>', '<a><path d="f"></path></a>'];
        yield 'href JaVaScRiPt: case' => ['<a xlink:href="JaVaScRiPt:alert(1)"/>', '<a></a>'];
        yield 'href javascript: with control chars' => ["<a xlink:href=\"java\tscript:alert(1)\"/>", '<a></a>'];
        yield 'href data:text/html' => ['<use href="data:text/html,xxx"/>', '<use></use>'];
        yield 'href vbscript:' => ['<a href="vbscript:msgbox(1)"/>', '<a></a>'];
        yield 'href data:image/svg+xml' => ['<a href="data:image/svg+xml;base64,PHN2Zy8+"/>', '<a></a>'];
        yield 'href unknown scheme' => ['<a href="weird:payload"/>', '<a></a>'];

        // SMIL animations installing a script-capable URL via href/xlink:href
        yield 'smil animate href' => ['<a><animate attributeName="href" to="javascript:alert(1)"/><path d="z"/></a>', '<a><path d="z"></path></a>'];
        yield 'smil set xlink:href' => ['<a><set attributeName="xlink:href" to="javascript:alert(1)"/></a>', '<a></a>'];

        // CDATA sections / processing instructions: XML-parsed (inert) but HTML-serialized (raw) -> mutation XSS
        yield 'cdata mutation (re-serialized)' => ['<g onclick="x"><![CDATA[<img src=x onerror=alert(1)>]]></g>', '<g></g>'];
        yield 'cdata standalone' => ['<g><![CDATA[<svg onload=alert(1)>]]></g>', '<g></g>'];
        yield 'cdata breaks out of parent' => ['<g><![CDATA[</g><img src=x onerror=alert(1)>]]></g>', '<g></g>'];
        yield 'processing instruction' => ['<g><?foo bar?></g>', '<g></g>'];

        // <style> is kept, but its attributes are sanitized and "</style>" breakouts are dropped
        yield 'style onload attribute' => ['<style onload="alert(1)">.a{fill:red}</style>', '<style>.a{fill:red}</style>'];
        yield 'style breakout via cdata' => ['<style><![CDATA[x{}</style><img src=x onerror=alert(1)>]]></style><path d="ok"/>', '<path d="ok"></path>'];
    }

    public static function provideSafeBodies(): iterable
    {
        yield 'path' => ['<path d="M0 0h24v24H0z" fill="none"/>'];
        yield 'group + circle' => ['<g><circle cx="12" cy="12" r="10"/></g>'];
        yield 'gradient' => ['<linearGradient id="a"><stop offset="0"/></linearGradient>'];
        yield 'clipPath' => ['<clipPath id="c"><rect width="1" height="1"/></clipPath>'];
        yield 'use fragment' => ['<use href="#a"/>'];
        yield 'anchor https' => ['<a href="https://example.com"><path d="x"/></a>'];
        yield 'image data:image' => ['<image href="data:image/png;base64,AAAA"/>'];
        yield 'animate opacity' => ['<animate attributeName="opacity" values="0;1"/>'];
        yield 'anchor mailto' => ['<a href="mailto:a@b.com"><path d="x"/></a>'];
        yield 'anchor tel' => ['<a href="tel:+33123"><path d="x"/></a>'];
        // SVG <use> sprites: fragment and relative/external references must be preserved.
        yield 'use sprite fragment' => ['<use xlink:href="#icon-a"/>'];
        yield 'use sprite external' => ['<use href="sprite.svg#icon-a"/>'];
        // <style> kept verbatim for theming (light/dark mode), including CDATA-wrapped CSS.
        yield 'style dark-mode' => ['<style>@media (prefers-color-scheme: dark){.i{fill:#fff}}</style>'];
        yield 'style cdata css' => ['<style><![CDATA[.a > b{fill:red}]]></style>'];
    }

    public static function provideInvalidBodies(): iterable
    {
        yield 'unclosed tag' => ['<path d="M0 0"'];
        yield 'mismatched tags' => ['<g><path d="z"></g>'];
        yield 'stray ampersand' => ['<path d="a & b"/>'];
    }

    public static function provideValidFiles(): iterable
    {
        $path = '<path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd"></path>';
        $attributes = ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'fill' => 'currentColor', 'class' => 'w-6 h-6'];

        yield 'valid1' => ['valid1', $attributes, $path];
        yield 'valid5 (nested group, xml prolog)' => ['valid5', $attributes, $path.'<g>'.$path.'</g>'];
    }

    public static function provideInvalidFiles(): iterable
    {
        yield 'empty file' => ['invalid1'];
        yield 'two root svgs' => ['invalid2'];
        yield 'wrapped svgs' => ['invalid3'];
        yield 'empty svg' => ['invalid4'];
    }
}
