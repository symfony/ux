<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Content\Converter;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Content\Converter\ContentConverterInterface;
use Symfony\UX\Editor\Content\Converter\ContentConverterRegistry;
use Symfony\UX\Editor\Content\EditorContentInterface;
use Symfony\UX\Editor\Content\HtmlContent;
use Symfony\UX\Editor\Exception\UnsupportedConversionException;

final class ContentConverterRegistryTest extends TestCase
{
    public function testIdentityConversion()
    {
        $reg = new ContentConverterRegistry([]);
        $in = new HtmlContent('<p>x</p>');
        self::assertSame($in, $reg->convert($in, 'ckeditor', 'ckeditor'));
    }

    public function testRegisteredConverterUsed()
    {
        $conv = new class implements ContentConverterInterface {
            public function getFrom(): string
            {
                return 'a';
            }

            public function getTo(): string
            {
                return 'b';
            }

            public function convert(EditorContentInterface $c): EditorContentInterface
            {
                return new HtmlContent('converted:'.$c->getRaw());
            }
        };
        $reg = new ContentConverterRegistry([$conv]);
        self::assertSame('converted:hi', $reg->convert(new HtmlContent('hi'), 'a', 'b')->getRaw());
    }

    public function testUnknownPairThrows()
    {
        $this->expectException(UnsupportedConversionException::class);
        new ContentConverterRegistry([])->convert(new HtmlContent(''), 'a', 'b');
    }
}
