<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Pagination\Navigation\PageLink;

#[CoversClass(PageLink::class)]
final class PageLinkTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $link = new PageLink(
            page: 5,
            url: '/items?page=5',
            isCurrent: true,
            isGap: false,
        );

        self::assertSame(5, $link->page);
        self::assertSame('/items?page=5', $link->url);
        self::assertTrue($link->isCurrent);
        self::assertFalse($link->isGap);
        self::assertSame(5, $link->getPage());
        self::assertSame('/items?page=5', $link->getUrl());
        self::assertTrue($link->isCurrent());
        self::assertFalse($link->isGap());
    }

    public function testGapLink(): void
    {
        $link = new PageLink(
            page: 0,
            url: '',
            isCurrent: false,
            isGap: true,
        );

        self::assertTrue($link->isGap);
        self::assertFalse($link->isCurrent);
    }

    public function testRegularLink(): void
    {
        $link = new PageLink(
            page: 3,
            url: '/items?page=3',
            isCurrent: false,
            isGap: false,
        );

        self::assertSame(3, $link->page);
        self::assertFalse($link->isCurrent);
        self::assertFalse($link->isGap);
    }
}
