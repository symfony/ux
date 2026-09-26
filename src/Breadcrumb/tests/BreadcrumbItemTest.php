<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Breadcrumb\BreadcrumbItem;

#[CoversClass(BreadcrumbItem::class)]
final class BreadcrumbItemTest extends TestCase
{
    public function testOnlyTheLabelIsRequired(): void
    {
        $item = new BreadcrumbItem('Products');

        self::assertSame('Products', $item->label);
        self::assertNull($item->url);
        self::assertSame([], $item->extra);
    }

    public function testNamedArgumentsLandOnTheMatchingProperties(): void
    {
        $item = new BreadcrumbItem('Products', '/products', ['icon' => 'tabler:package']);

        self::assertSame('Products', $item->label);
        self::assertSame('/products', $item->url);
        self::assertSame(['icon' => 'tabler:package'], $item->extra);
    }
}
