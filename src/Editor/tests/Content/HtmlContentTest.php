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
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\UX\Editor\Content\EditorContentFormat;
use Symfony\UX\Editor\Content\HtmlContent;

final class HtmlContentTest extends TestCase
{
    public function testFormatAndRaw(): void
    {
        $c = new HtmlContent('<p>hi</p>');
        self::assertSame(EditorContentFormat::Html, $c->getFormat());
        self::assertSame('<p>hi</p>', $c->getRaw());
    }

    public function testIsEmpty(): void
    {
        self::assertTrue((new HtmlContent(''))->isEmpty());
        self::assertTrue((new HtmlContent('   '))->isEmpty());
        self::assertTrue((new HtmlContent('<p>  </p>'))->isEmpty());
        self::assertFalse((new HtmlContent('<p>hi</p>'))->isEmpty());
    }

    public function testFromString(): void
    {
        $c = HtmlContent::fromString('<b>x</b>', ['bridgeId' => 'ckeditor']);
        self::assertSame('<b>x</b>', $c->html);
        self::assertSame(['bridgeId' => 'ckeditor'], $c->getMetadata());
    }

    public function testGetSanitizedWithProvidedSanitizer(): void
    {
        $s = new HtmlSanitizer((new HtmlSanitizerConfig())->allowSafeElements());
        $out = (new HtmlContent('<script>x</script><p>ok</p>'))->getSanitized($s);
        self::assertStringNotContainsString('<script>', $out);
        self::assertStringContainsString('<p>ok</p>', $out);
    }

    public function testGetSanitizedWithoutSanitizerReturnsRaw(): void
    {
        self::assertSame('<p>x</p>', (new HtmlContent('<p>x</p>'))->getSanitized(null));
    }
}
