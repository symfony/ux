<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Bridge\Format\Page;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\Format\Page\PageAssetExtractor;
use Symfony\UX\Editor\Content\PageContent;

final class PageAssetExtractorTest extends TestCase
{
    public function testExtractsFromAssetsField(): void
    {
        $page = new PageContent('<p>x</p>', '', [
            ['type' => 'image', 'url' => '/a.png'],
            ['type' => 'image', 'url' => '/b.png'],
        ]);
        self::assertSame(['/a.png', '/b.png'], (new PageAssetExtractor())->extractUrls($page));
    }

    public function testWalksComponentTreeForSrc(): void
    {
        $page = new PageContent('', '', [], [
            ['type' => 'section', 'children' => [
                ['type' => 'image', 'src' => '/c.png'],
                ['type' => 'image', 'src' => '/d.png'],
            ]],
        ]);
        $urls = (new PageAssetExtractor())->extractUrls($page);
        sort($urls);
        self::assertSame(['/c.png', '/d.png'], $urls);
    }

    public function testDedupes(): void
    {
        $page = new PageContent('', '', [['type' => 'image', 'url' => '/x.png']], [['type' => 'image', 'src' => '/x.png']]);
        self::assertSame(['/x.png'], (new PageAssetExtractor())->extractUrls($page));
    }
}
