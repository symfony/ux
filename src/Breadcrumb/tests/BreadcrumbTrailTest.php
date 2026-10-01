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
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
use Symfony\UX\Breadcrumb\BreadcrumbTrail;

#[CoversClass(BreadcrumbTrail::class)]
final class BreadcrumbTrailTest extends TestCase
{
    public function testStartsEmpty(): void
    {
        $trail = new BreadcrumbTrail();

        self::assertTrue($trail->isEmpty());
        self::assertSame([], $trail->all());
        self::assertSame('', $trail->route);
        self::assertSame([], $trail->routeParameters);
        self::assertSame([], $trail->context);
    }

    public function testExposesTheContextItWasBuiltWith(): void
    {
        $trail = new BreadcrumbTrail('product_view', ['slug' => 'blue-sneakers'], ['product' => 'entity']);

        self::assertSame('product_view', $trail->route);
        self::assertSame(['slug' => 'blue-sneakers'], $trail->routeParameters);
        self::assertSame(['product' => 'entity'], $trail->context);
    }

    public function testAppendPreservesOrderAcrossSingleAndVariadicCalls(): void
    {
        $trail = new BreadcrumbTrail();
        $first = new Breadcrumb('first');
        $second = new Breadcrumb('second');
        $third = new Breadcrumb('third');

        $trail->append($first);
        $trail->append($second, $third);

        self::assertFalse($trail->isEmpty());
        self::assertSame([$first, $second, $third], $trail->all());
    }

    public function testPrependPutsACrumbBeforeTheExistingOnes(): void
    {
        $leaf = new Breadcrumb('leaf');
        $root = new Breadcrumb('root');

        $trail = new BreadcrumbTrail();
        $trail->append($leaf);
        $trail->prepend($root);

        self::assertSame([$root, $leaf], $trail->all());
    }

    public function testPrependKeepsTheRelativeOrderOfThePrependedCrumbs(): void
    {
        $leaf = new Breadcrumb('leaf');
        $root = new Breadcrumb('root');
        $section = new Breadcrumb('section');

        $trail = new BreadcrumbTrail();
        $trail->append($leaf);
        $trail->prepend($root, $section);

        self::assertSame([$root, $section, $leaf], $trail->all());
    }

    public function testAppendWithNoArgumentsIsANoop(): void
    {
        $trail = new BreadcrumbTrail();
        $trail->append();
        $trail->prepend();

        self::assertTrue($trail->isEmpty());
    }
}
