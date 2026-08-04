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
use Symfony\UX\Pagination\Adapter\ArrayPaginationAdapter;
use Symfony\UX\Pagination\CursorPagination;
use Symfony\UX\Pagination\Exception\OffsetLimitExceededException;
use Symfony\UX\Pagination\Exception\RuntimeException;
use Symfony\UX\Pagination\Pagination;
use Symfony\UX\Pagination\PaginationBuilder;
use Symfony\UX\Pagination\Paginator;
use Symfony\UX\Pagination\PaginatorInterface;

#[CoversClass(Paginator::class)]
final class PaginatorTest extends TestCase
{
    private Paginator $paginator;

    protected function setUp(): void
    {
        $this->paginator = new Paginator([new ArrayPaginationAdapter()], cursorCodec: new \Symfony\UX\Pagination\Cursor\CursorCodec('test-secret'));
    }

    public function testImplementsInterface()
    {
        self::assertInstanceOf(PaginatorInterface::class, $this->paginator);
    }

    public function testPaginateArray()
    {
        $result = $this->paginator->paginate(range(1, 100), 1, 10);

        self::assertInstanceOf(Pagination::class, $result);
        self::assertSame(1, $result->getCurrentPage());
        self::assertSame(10, $result->getItemsPerPage());
        self::assertSame(100, $result->getTotalItems());
        self::assertSame(10, $result->getTotalPages());
        self::assertCount(10, $result);
    }

    public function testPaginateSecondPage()
    {
        $result = $this->paginator->paginate(range(1, 100), 3, 20);

        self::assertSame(3, $result->getCurrentPage());
        self::assertSame(20, $result->getItemsPerPage());
        self::assertSame([41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60], $result->getItems());
    }

    public function testPaginateUsesConfiguredDefaultPerPage()
    {
        $paginator = new Paginator(
            [new ArrayPaginationAdapter()],
            defaultPerPage: 50,
        );

        $result = $paginator->paginate(range(1, 200), 1);

        self::assertSame(50, $result->getItemsPerPage());
        self::assertCount(50, $result);
        self::assertSame(4, $result->getTotalPages());
    }

    public function testPaginateUsesConfiguredMaximumOffset()
    {
        $paginator = new Paginator(
            [new ArrayPaginationAdapter()],
            defaultMaxOffset: 20,
        );

        $this->expectException(OffsetLimitExceededException::class);

        $paginator->paginate(range(1, 100), page: 3, perPage: 20);
    }

    public function testFromCallbacksUsesConfiguredDefaultPerPage()
    {
        $paginator = new Paginator(
            [new ArrayPaginationAdapter()],
            defaultPerPage: 15,
        );

        $items = range(1, 100);
        $result = $paginator
            ->fromCallbacks(
                static fn (int $offset, int $limit) => \array_slice($items, $offset, $limit),
                static fn () => \count($items),
            )
            ->paginate(1);

        self::assertSame(15, $result->getItemsPerPage());
        self::assertCount(15, $result);
    }

    public function testFromCallbacksExposesTheCompleteNumberedBuilder()
    {
        $items = range(1, 5);
        $countCalls = 0;

        $result = $this->paginator
            ->fromCallbacks(
                static fn (int $offset, int $limit): array => \array_slice($items, $offset, $limit),
                static function () use (&$countCalls, $items): int {
                    ++$countCalls;

                    return \count($items);
                },
            )
            ->perPage(2)
            ->lookahead()
            ->paginate();

        self::assertSame([1, 2], $result->getItems());
        self::assertTrue($result->hasNext());
        self::assertSame(0, $countCalls);
    }

    public function testFromCallbacksAcceptsInvokableServices()
    {
        $items = range(1, 10);
        $slicer = new class($items) {
            /** @param list<int> $items */
            public function __construct(private readonly array $items)
            {
            }

            /** @return list<int> */
            public function __invoke(int $offset, int $limit): array
            {
                return array_values(\array_slice($this->items, $offset, $limit));
            }
        };
        $counter = new class($items) {
            /** @param list<int> $items */
            public function __construct(private readonly array $items)
            {
            }

            public function __invoke(): int
            {
                return \count($this->items);
            }
        };

        $result = $this->paginator
            ->fromCallbacks($slicer, $counter)
            ->perPage(4)
            ->paginate();

        self::assertSame([1, 2, 3, 4], $result->getItems());
        self::assertSame(10, $result->getTotalItems());
    }

    public function testCursorBuilderUsesConfiguredDefaultPerPage()
    {
        $paginator = new Paginator(
            [new ArrayPaginationAdapter()],
            defaultPerPage: 5,
            cursorCodec: new \Symfony\UX\Pagination\Cursor\CursorCodec('test-secret'),
        );

        $source = [];
        for ($i = 1; $i <= 50; ++$i) {
            $source[] = ['id' => $i];
        }

        $result = $paginator
            ->cursor($source)
            ->orderBy('id')
            ->context('items')
            ->paginate();

        self::assertSame(5, $result->getItemsPerPage());
        self::assertCount(5, $result);
    }

    public function testPaginateExplicitPerPageOverridesDefault()
    {
        $paginator = new Paginator(
            [new ArrayPaginationAdapter()],
            defaultPerPage: 50,
        );

        $result = $paginator->paginate(range(1, 200), 1, 10);

        self::assertSame(10, $result->getItemsPerPage());
        self::assertCount(10, $result);
    }

    public function testPaginateThrowsForInvalidPage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->paginator->paginate(range(1, 10), 0);
    }

    public function testPaginateThrowsForInvalidPerPage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->paginator->paginate(range(1, 10), 1, 0);
    }

    public function testPaginateThrowsForUnsupportedSource()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/No "offset" pagination adapter/');
        $this->paginator->paginate(new \stdClass());
    }

    public function testQueryReturnsBuilder()
    {
        $builder = $this->paginator->query(range(1, 50));

        self::assertInstanceOf(PaginationBuilder::class, $builder);
    }

    public function testCursorRequiresConfiguredCodec()
    {
        $paginator = new Paginator([new ArrayPaginationAdapter()]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires a CursorCodecInterface');
        $paginator->cursor([]);
    }

    public function testCursorBuilderPaginatesAnArray()
    {
        $source = [];
        for ($i = 1; $i <= 50; ++$i) {
            $source[] = ['id' => $i, 'name' => 'Item '.$i];
        }

        $result = $this->paginator
            ->cursor($source)
            ->orderBy('id')
            ->perPage(10)
            ->context('items')
            ->paginate();

        self::assertInstanceOf(CursorPagination::class, $result);
        self::assertCount(10, $result);
        self::assertTrue($result->hasNext());
        self::assertNotNull($result->getNextCursor());
    }

    public function testCursorBuilderRequiresAStableApplicationContextForArrays()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires an explicit context()');

        $this->paginator
            ->cursor([['id' => 1]])
            ->orderBy('id')
            ->paginate();
    }

    public function testCursorBuilderContextRemainsValidWhenTheSourceMutates()
    {
        $source = array_map(static fn (int $id): array => ['id' => $id], range(1, 15));

        $first = $this->paginator
            ->cursor($source)
            ->orderBy('id', 'ASC')
            ->perPage(5)
            ->context('tenant-a:items')
            ->paginate();

        $mutated = array_values(array_filter(
            $source,
            static fn (array $item): bool => !\in_array($item['id'], [3, 7], true),
        ));
        $second = $this->paginator
            ->cursor($mutated)
            ->orderBy('id', 'ASC')
            ->cursor($first->getNextCursor())
            ->perPage(5)
            ->context('tenant-a:items')
            ->paginate();

        self::assertSame([6, 8, 9, 10, 11], array_column($second->getItems(), 'id'));
    }

    public function testCursorBuilderSupportsACustomField()
    {
        $source = [];
        for ($i = 1; $i <= 30; ++$i) {
            $source[] = ['id' => $i, 'name' => 'Item '.$i, 'position' => $i * 10];
        }

        $result = $this->paginator
            ->cursor($source)
            ->orderBy('position')
            ->perPage(10)
            ->context('items')
            ->paginate();

        self::assertInstanceOf(CursorPagination::class, $result);
        self::assertCount(10, $result);
        self::assertTrue($result->hasNext());
        self::assertNotNull($result->getNextCursor());

        // Navigate to next page using the cursor
        $nextCursor = $result->getNextCursor();
        \assert(\is_string($nextCursor));
        $page2 = $this->paginator
            ->cursor($source)
            ->orderBy('position')
            ->cursor($nextCursor)
            ->perPage(10)
            ->context('items')
            ->paginate();
        self::assertCount(10, $page2);
        self::assertTrue($page2->hasNext());
    }

    public function testCursorBuilderSupportsADirection()
    {
        $source = [];
        for ($i = 1; $i <= 25; ++$i) {
            $source[] = ['id' => $i, 'name' => 'Item '.$i];
        }

        $result = $this->paginator
            ->cursor($source)
            ->orderBy('id', 'ASC')
            ->perPage(10)
            ->context('items')
            ->paginate();

        self::assertCount(10, $result);
        self::assertTrue($result->hasNext());
    }

    public function testCursorBuilderAllowsAnAdapterOwnedOrder()
    {
        $source = new \stdClass();
        $adapter = new class implements \Symfony\UX\Pagination\Adapter\CursorAdapterInterface {
            public function supports(mixed $source): bool
            {
                return $source instanceof \stdClass;
            }

            public function resolveCursorOrder(mixed $source, ?array $fields, ?string $direction): \Symfony\UX\Pagination\Cursor\CursorOrder
            {
                if (null !== $fields || null !== $direction) {
                    throw new \LogicException('The remote adapter must own its order.');
                }

                return \Symfony\UX\Pagination\Cursor\CursorOrder::byIdentity('remote-order');
            }

            public function getCursorContext(mixed $source, ?string $context): string
            {
                return 'remote-context';
            }

            public function sliceWithCursor(
                mixed $source,
                ?\Symfony\UX\Pagination\Cursor\CursorBoundary $boundary,
                int $limit,
                \Symfony\UX\Pagination\Cursor\CursorOrder $order,
            ): \Symfony\UX\Pagination\Cursor\CursorSlice {
                return new \Symfony\UX\Pagination\Cursor\CursorSlice([42], null, null, false);
            }
        };
        $paginator = new Paginator(
            [$adapter],
            cursorCodec: new \Symfony\UX\Pagination\Cursor\CursorCodec('test-secret'),
        );

        $pagination = $paginator->cursor($source)->paginate();

        self::assertSame([42], $pagination->getItems());
    }

    public function testCursorBuilderThrowsForInvalidDirection()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('direction must be "ASC" or "DESC"');
        $this->paginator->cursor([['id' => 1]])->orderBy('id', 'INVALID');
    }

    public function testCursorBuilderThrowsForInvalidPerPage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->paginator->cursor([['id' => 1]])->perPage(0);
    }

    public function testCursorBuilderThrowsForUnsupportedSource()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->paginator->cursor(new \stdClass())->paginate();
    }

    public function testFromCallbacksThrowsForInvalidPage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Page must be >= 1');

        $this->paginator
            ->fromCallbacks(
                static fn (int $offset, int $limit) => [],
                static fn () => 0,
            )
            ->paginate(0);
    }

    public function testFromCallbacksThrowsForInvalidPerPage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('perPage must be >= 1');

        $this->paginator
            ->fromCallbacks(
                static fn (int $offset, int $limit) => [],
                static fn () => 0,
            )
            ->perPage(0);
    }

    public function testCursorBuilderThrowsForNonCursorAdapter()
    {
        $adapter = $this->createStub(\Symfony\UX\Pagination\Adapter\PaginationAdapterInterface::class);
        $adapter->method('supports')->willReturn(true);

        $paginator = new Paginator([$adapter], cursorCodec: new \Symfony\UX\Pagination\Cursor\CursorCodec('test-secret'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No cursor pagination adapter found');

        $paginator->cursor('some source')->paginate();
    }

    public function testPaginateDefaultsToPage1WithoutRequest()
    {
        $result = $this->paginator->paginate(range(1, 50));

        self::assertSame(1, $result->getCurrentPage());
    }

    public function testPaginateDefaultsToPage1WhenRequestHasNoPageParam()
    {
        $request = new \Symfony\Component\HttpFoundation\Request(query: ['sort' => 'name']);
        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $requestStack->push($request);

        $paginator = new Paginator(
            [new ArrayPaginationAdapter()],
            requestStack: $requestStack,
        );

        $result = $paginator->paginate(range(1, 50));

        self::assertSame(1, $result->getCurrentPage());
    }

    public function testPaginateResolvesPageFromRequest()
    {
        $request = new \Symfony\Component\HttpFoundation\Request(query: ['page' => '3']);
        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $requestStack->push($request);

        $paginator = new Paginator(
            [new ArrayPaginationAdapter()],
            requestStack: $requestStack,
        );

        $result = $paginator->paginate(range(1, 100));

        self::assertSame(3, $result->getCurrentPage());
    }

    public function testPaginateResolvesPageFromPathParameter()
    {
        $request = new \Symfony\Component\HttpFoundation\Request();
        $request->attributes->set('_route', 'blog_list');
        $request->attributes->set('page', 5);
        $request->attributes->set('_route_params', ['page' => 5]);

        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $requestStack->push($request);

        $paginator = new Paginator(
            [new ArrayPaginationAdapter()],
            requestStack: $requestStack,
        );

        $result = $paginator->paginate(range(1, 100));

        self::assertSame(5, $result->getCurrentPage());
    }

    public function testPaginatePathParameterTakesPriorityOverQueryParameter()
    {
        $request = new \Symfony\Component\HttpFoundation\Request(query: ['page' => '2']);
        $request->attributes->set('_route', 'blog_list');
        $request->attributes->set('page', 7);
        $request->attributes->set('_route_params', ['page' => 7]);

        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $requestStack->push($request);

        $paginator = new Paginator(
            [new ArrayPaginationAdapter()],
            requestStack: $requestStack,
        );

        $result = $paginator->paginate(range(1, 100));

        self::assertSame(7, $result->getCurrentPage());
    }

    public function testPaginateExplicitPageOverridesPathParameter()
    {
        $requestStack = $this->createMock(\Symfony\Component\HttpFoundation\RequestStack::class);
        $requestStack->expects(self::never())->method('getCurrentRequest');

        $paginator = new Paginator(
            [new ArrayPaginationAdapter()],
            requestStack: $requestStack,
        );

        $result = $paginator->paginate(range(1, 100), 3);

        self::assertSame(3, $result->getCurrentPage());
    }

    public function testCursorBuilderReadsCursorFromRequest()
    {
        $source = [];
        for ($i = 1; $i <= 30; ++$i) {
            $source[] = ['id' => $i];
        }

        $cursor = $this->signedCursor(10);
        $request = new \Symfony\Component\HttpFoundation\Request(query: ['cursor' => $cursor]);
        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $requestStack->push($request);

        $paginator = new Paginator(
            [new ArrayPaginationAdapter()],
            requestStack: $requestStack,
            cursorCodec: new \Symfony\UX\Pagination\Cursor\CursorCodec('test-secret'),
        );

        $result = $paginator
            ->cursor($source)
            ->orderBy('id', 'ASC')
            ->perPage(5)
            ->context('items')
            ->paginate();

        self::assertSame($cursor, $result->getCursor());
        self::assertSame(11, $result->getItems()[0]['id']);
    }

    public function testCursorBuilderExplicitCursorWinsOverRequest()
    {
        $source = [];
        for ($i = 1; $i <= 30; ++$i) {
            $source[] = ['id' => $i];
        }

        $requestCursor = $this->signedCursor(10);
        $explicitCursor = $this->signedCursor(20);
        $request = new \Symfony\Component\HttpFoundation\Request(query: ['cursor' => $requestCursor]);
        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $requestStack->push($request);

        $paginator = new Paginator(
            [new ArrayPaginationAdapter()],
            requestStack: $requestStack,
            cursorCodec: new \Symfony\UX\Pagination\Cursor\CursorCodec('test-secret'),
        );

        $result = $paginator
            ->cursor($source)
            ->orderBy('id', 'ASC')
            ->cursor($explicitCursor)
            ->perPage(5)
            ->context('items')
            ->paginate();

        self::assertSame(21, $result->getItems()[0]['id']);
    }

    public function testCursorBuilderUsesConfiguredCursorParameter()
    {
        $source = [];
        for ($i = 1; $i <= 30; ++$i) {
            $source[] = ['id' => $i];
        }

        $cursor = $this->signedCursor(10);
        $request = new \Symfony\Component\HttpFoundation\Request(query: ['after' => $cursor]);
        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $requestStack->push($request);

        $paginator = new Paginator(
            [new ArrayPaginationAdapter()],
            requestStack: $requestStack,
            defaultCursorParam: 'after',
            cursorCodec: new \Symfony\UX\Pagination\Cursor\CursorCodec('test-secret'),
        );

        $result = $paginator
            ->cursor($source)
            ->orderBy('id', 'ASC')
            ->perPage(5)
            ->context('items')
            ->paginate();

        // Read from ?after=... and generated URLs use the same parameter
        self::assertSame(11, $result->getItems()[0]['id']);
        self::assertStringContainsString('after=', (string) $result->getNextUrl());
    }

    public function testPaginateAllowsExplicitPerPageAboveBuilderGuard()
    {
        $paginator = new Paginator([new ArrayPaginationAdapter()]);

        $result = $paginator->paginate(range(1, 5000), page: 1, perPage: 2000);

        self::assertSame(2000, $result->getItemsPerPage());
        self::assertCount(2000, $result->getItems());
    }

    private function signedCursor(int $value): string
    {
        $fingerprint = \Symfony\UX\Pagination\Cursor\CursorOrder::byFields(['id'], 'ASC')->getFingerprint();

        return new \Symfony\UX\Pagination\Cursor\CursorCodec('test-secret')->encode([$value], true, $fingerprint, 'items');
    }
}
