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
use Symfony\UX\Editor\Content\EditorContentFormat;
use Symfony\UX\Editor\Content\PageContent;

final class PageContentTest extends TestCase
{
    public function testFormat(): void
    {
        self::assertSame(EditorContentFormat::Page, (new PageContent(''))->getFormat());
    }

    public function testRawBundle(): void
    {
        $p = new PageContent(html: '<h1>x</h1>', css: 'h1{color:red}', components: [['type' => 'h1']]);
        $raw = $p->getRaw();
        self::assertSame('<h1>x</h1>',  $raw['html']);
        self::assertSame('h1{color:red}', $raw['css']);
        self::assertSame([['type' => 'h1']], $raw['components']);
    }

    public function testIsEmpty(): void
    {
        self::assertTrue((new PageContent(''))->isEmpty());
        self::assertFalse((new PageContent('<p>x</p>'))->isEmpty());
        self::assertFalse((new PageContent('', '', [], [['type' => 'p']]))->isEmpty());
    }

    public function testExtractAssets(): void
    {
        $assets = [['type' => 'image', 'url' => '/x.png']];
        self::assertSame($assets, (new PageContent('', '', $assets))->extractAssets());
    }

    public function testFromBundle(): void
    {
        $p = PageContent::fromBundle([
            'html' => '<p>x</p>',
            'css'  => 'p{}',
            'assets' => [['type' => 'image', 'url' => '/x.png']],
            'components' => [['type' => 'p']],
        ]);
        self::assertSame('<p>x</p>', $p->html);
        self::assertSame('p{}', $p->css);
        self::assertCount(1, $p->assets);
        self::assertCount(1, $p->components);
    }
}
