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
use Symfony\UX\Pagination\Cursor\CursorBoundary;
use Symfony\UX\Pagination\Cursor\CursorOrder;
use Symfony\UX\Pagination\Cursor\CursorSlice;

#[CoversClass(CursorBoundary::class)]
#[CoversClass(CursorOrder::class)]
#[CoversClass(CursorSlice::class)]
final class CursorValueObjectTest extends TestCase
{
    public function testBoundaryExposesItsValuesAndDirection(): void
    {
        $boundary = new CursorBoundary([42, 'release'], false);

        self::assertSame([42, 'release'], $boundary->getValues());
        self::assertFalse($boundary->pointsForward());
    }

    public function testSliceExposesItemsAndBoundaries(): void
    {
        $next = new CursorBoundary([3]);
        $previous = new CursorBoundary([1], false);
        $slice = new CursorSlice([['id' => 1], ['id' => 2]], $next, $previous, true);

        self::assertSame([['id' => 1], ['id' => 2]], $slice->getItems());
        self::assertSame($next, $slice->getNextBoundary());
        self::assertSame($previous, $slice->getPreviousBoundary());
        self::assertTrue($slice->hasNext());
    }

    public function testFieldOrderExposesNormalizedFieldsAndStableFingerprint(): void
    {
        $order = CursorOrder::byFields(['createdAt', 'id'], 'desc');

        self::assertSame(['createdAt', 'id'], $order->getFields());
        self::assertSame('DESC', $order->getDirection());
        self::assertSame(
            $order->getFingerprint(),
            CursorOrder::byFields(['createdAt', 'id'], 'DESC')->getFingerprint(),
        );
        self::assertNotSame(
            $order->getFingerprint(),
            CursorOrder::byFields(['id', 'createdAt'], 'DESC')->getFingerprint(),
        );
    }

    public function testOpaqueOrderIdentityIsStableAndNotFieldBased(): void
    {
        $order = CursorOrder::byIdentity('github:pull-requests:created-desc');

        self::assertNull($order->getFields());
        self::assertNull($order->getDirection());
        self::assertSame(
            $order->getFingerprint(),
            CursorOrder::byIdentity('github:pull-requests:created-desc')->getFingerprint(),
        );
        self::assertNotSame(
            $order->getFingerprint(),
            CursorOrder::byIdentity('github:pull-requests:updated-desc')->getFingerprint(),
        );
    }

    public function testOrderRejectsInvalidDefinitions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CursorOrder::byFields([], 'ASC');
    }

    public function testOrderRejectsInvalidDirection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CursorOrder::byFields(['id'], 'sideways');
    }

    public function testOpaqueOrderRejectsEmptyIdentity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CursorOrder::byIdentity('');
    }
}
