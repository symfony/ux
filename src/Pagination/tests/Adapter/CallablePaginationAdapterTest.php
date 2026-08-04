<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests\Adapter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Pagination\Adapter\CallablePaginationAdapter;

#[CoversClass(CallablePaginationAdapter::class)]
final class CallablePaginationAdapterTest extends TestCase
{
    public function testSupportsReturnsFalse()
    {
        $adapter = new CallablePaginationAdapter(
            static fn (int $offset, int $limit): array => [],
            static fn (): int => 0,
        );

        // Callable adapter is never auto-discovered
        self::assertFalse($adapter->supports('anything'));
    }

    public function testSliceAndCount()
    {
        $data = range(1, 50);

        $adapter = new CallablePaginationAdapter(
            static fn (int $offset, int $limit): array => \array_slice($data, $offset, $limit),
            static fn (): int => \count($data),
        );

        self::assertSame(50, $adapter->count(null));
        self::assertSame([1, 2, 3, 4, 5], $adapter->slice(null, 0, 5));
        self::assertSame([11, 12, 13, 14, 15], $adapter->slice(null, 10, 5));
    }

    public function testSliceWithLookahead()
    {
        $data = range(1, 25);

        $adapter = new CallablePaginationAdapter(
            static fn (int $offset, int $limit): array => \array_slice($data, $offset, $limit),
            static fn (): int => \count($data),
        );

        [$items, $hasMore] = $adapter->sliceWithLookahead(null, 0, 10);
        self::assertCount(10, $items);
        self::assertTrue($hasMore);

        [$items, $hasMore] = $adapter->sliceWithLookahead(null, 20, 10);
        self::assertCount(5, $items);
        self::assertFalse($hasMore);
    }

    public function testSliceThrowsForNonArray()
    {
        $adapter = new CallablePaginationAdapter(
            static fn (int $offset, int $limit): string => 'not an array', // @phpstan-ignore return.type
            static fn (): int => 0,
        );

        $this->expectException(\RuntimeException::class);
        $adapter->slice(null, 0, 10);
    }

    public function testSliceWithLookaheadThrowsForNonArray()
    {
        $adapter = new CallablePaginationAdapter(
            static fn (int $offset, int $limit): string => 'not an array', // @phpstan-ignore return.type
            static fn (): int => 0,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Slicer callback must return an array');
        $adapter->sliceWithLookahead(null, 0, 10);
    }

    public function testCountRejectsANonIntegerResult()
    {
        $adapter = new CallablePaginationAdapter(
            static fn (int $offset, int $limit): array => [],
            static fn (): string => '10', // @phpstan-ignore argument.type
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Counter callback must return a non-negative integer');
        $adapter->count(null);
    }

    public function testCountRejectsANegativeResult()
    {
        $adapter = new CallablePaginationAdapter(
            static fn (int $offset, int $limit): array => [],
            static fn (): int => -1,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Counter callback must return a non-negative integer');
        $adapter->count(null);
    }
}
