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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Pagination\Adapter\ArrayPaginationAdapter;
use Symfony\UX\Pagination\Adapter\CursorAdapterInterface;
use Symfony\UX\Pagination\Adapter\OffsetAdapterInterface;
use Symfony\UX\Pagination\Cursor\CursorBoundary;
use Symfony\UX\Pagination\Cursor\CursorOrder;
use Symfony\UX\Pagination\Cursor\CursorSlice;
use Symfony\UX\Pagination\Exception\OffsetLimitExceededException;
use Symfony\UX\Pagination\Exception\OutOfRangePageException;
use Symfony\UX\Pagination\Pagination;
use Symfony\UX\Pagination\PaginationBuilder;

#[CoversClass(PaginationBuilder::class)]
final class PaginationBuilderTest extends TestCase
{
    public function testBasicBuild()
    {
        $builder = $this->builder(range(1, 100));
        $result = $builder->paginate(page: 1);

        self::assertInstanceOf(Pagination::class, $result);
        self::assertSame(1, $result->getCurrentPage());
        self::assertCount(20, $result); // default perPage
    }

    #[DataProvider('invalidRequestPages')]
    public function testInvalidRequestPageIsRejected(mixed $value)
    {
        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $requestStack->push(new \Symfony\Component\HttpFoundation\Request(['page' => $value]));

        $builder = new PaginationBuilder(
            range(1, 100),
            [new ArrayPaginationAdapter()],
            requestStack: $requestStack,
        );

        $this->expectException(\Symfony\Component\HttpKernel\Exception\BadRequestHttpException::class);
        $this->expectExceptionMessage('pagination parameter "page" must be a positive integer');
        $builder->paginate();
    }

    public static function invalidRequestPages(): iterable
    {
        yield 'zero integer' => [0];
        yield 'negative string' => ['-1'];
        yield 'float string' => ['1.5'];
        yield 'letters' => ['foo'];
        yield 'array' => [['2']];
        yield 'integer overflow' => [str_repeat('9', 100)];
    }

    public function testRoutePageHasPriorityOverQueryPage()
    {
        $request = new \Symfony\Component\HttpFoundation\Request(['page' => '9']);
        $request->attributes->set('page', '3');
        $request->attributes->set('_route_params', ['page' => '3']);
        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $requestStack->push($request);

        $result = new PaginationBuilder(
            range(1, 100),
            [new ArrayPaginationAdapter()],
            requestStack: $requestStack,
        )->paginate();

        self::assertSame(3, $result->getCurrentPage());
    }

    public function testPerPage()
    {
        $result = $this->builder(range(1, 100))->perPage(5)->paginate(page: 2);

        self::assertSame(2, $result->getCurrentPage());
        self::assertSame(5, $result->getItemsPerPage());
        self::assertSame([6, 7, 8, 9, 10], $result->getItems());
    }

    public function testPerPageThrowsForInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder([])->perPage(0);
    }

    public function testPerPageRejectsIntegerOverflow()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('less than PHP_INT_MAX');

        $this->builder([])->perPage(\PHP_INT_MAX);
    }

    public function testDeveloperPerPageIsNotSilentlyCapped()
    {
        $result = $this->builder(range(1, 2000))->perPage(1500)->paginate();

        self::assertSame(1500, $result->getItemsPerPage());
        self::assertCount(1500, $result);
    }

    public function testSlidingRejectsEmptyWindow()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('size must be >= 1.');
        $this->builder([])->sliding(0);
    }

    public function testFixedRejectsEmptyWindow()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('size must be >= 1.');
        $this->builder([])->fixed(0);
    }

    public function testSlidingMode()
    {
        $result = $this->builder(range(1, 200))->sliding(7)->paginate(page: 10);

        $links = iterator_to_array($result->getPages());
        self::assertNotEmpty($links);
    }

    public function testFixedMode()
    {
        $result = $this->builder(range(1, 200))->fixed(5)->paginate(page: 3);

        $links = iterator_to_array($result->getPages());
        self::assertNotEmpty($links);
    }

    public function testFullMode()
    {
        $result = $this->builder(range(1, 50))->perPage(10)->full()->paginate(page: 3);

        $links = iterator_to_array($result->getPages());
        // Full mode should show all 5 pages (50 items / 10 per page)
        $nonGaps = array_filter($links, static fn ($l) => !$l->isGap);
        self::assertCount(5, $nonGaps);
    }

    public function testFullRejectsInvalidMaximum()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('maxPages must be >= 1.');
        $this->builder([])->full(0);
    }

    public function testLookahead()
    {
        $result = $this->builder(range(1, 50))->lookahead()->paginate(page: 1);

        self::assertNull($result->getTotalItems());
        self::assertNull($result->getTotalPages());
        self::assertTrue($result->hasNext());
        self::assertCount(20, $result);
    }

    public function testLookaheadCannotBeCombinedWithAnExactTotal()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('lookahead() cannot be combined with total()');

        $this->builder(range(1, 50))
            ->lookahead()
            ->total(50)
            ->paginate();
    }

    public function testLookaheadCannotThrowOnAnUnknownLastPage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('lookahead() cannot be combined with throwOnOutOfRange()');

        $this->builder(range(1, 50))
            ->lookahead()
            ->throwOnOutOfRange()
            ->paginate();
    }

    public function testLookaheadRejectsAnAdapterWithoutLookaheadSupport()
    {
        $source = new \stdClass();
        $adapter = new class implements OffsetAdapterInterface {
            public function supports(mixed $source): bool
            {
                return true;
            }

            public function slice(mixed $source, int $offset, int $limit): array
            {
                return [];
            }

            public function count(mixed $source): int
            {
                return 0;
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No "lookahead" pagination adapter found');

        new PaginationBuilder($source, [$adapter])->lookahead()->paginate();
    }

    public function testExplicitAdapterMustSupportTheSelectedMode()
    {
        $adapter = new class implements OffsetAdapterInterface {
            public function supports(mixed $source): bool
            {
                return true;
            }

            public function slice(mixed $source, int $offset, int $limit): array
            {
                return [];
            }

            public function count(mixed $source): int
            {
                return 0;
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not support "lookahead" pagination');

        new PaginationBuilder(new \stdClass(), [], adapter: $adapter)->lookahead()->paginate();
    }

    public function testOffsetResolutionSkipsACursorOnlyAdapterForTheSameSource()
    {
        $source = new \stdClass();
        $cursorAdapter = new class implements CursorAdapterInterface {
            public function supports(mixed $source): bool
            {
                return $source instanceof \stdClass;
            }

            public function resolveCursorOrder(mixed $source, ?array $fields, ?string $direction): CursorOrder
            {
                return CursorOrder::byIdentity('remote-order');
            }

            public function getCursorContext(mixed $source, ?string $context): string
            {
                return 'remote-context';
            }

            public function sliceWithCursor(mixed $source, ?CursorBoundary $boundary, int $limit, CursorOrder $order): CursorSlice
            {
                return new CursorSlice([], null, null, false);
            }
        };
        $offsetAdapter = new class implements OffsetAdapterInterface {
            public function supports(mixed $source): bool
            {
                return $source instanceof \stdClass;
            }

            public function slice(mixed $source, int $offset, int $limit): array
            {
                return ['offset'];
            }

            public function count(mixed $source): int
            {
                return 1;
            }
        };

        $pagination = new PaginationBuilder($source, [$cursorAdapter, $offsetAdapter])->paginate();

        self::assertSame(['offset'], $pagination->getItems());
    }

    public function testAppends()
    {
        $result = $this->builder(range(1, 100))
            ->queryParameters(['q' => 'test', 'sort' => 'name'])
            ->paginate(page: 1);

        $url = $result->getUrl(2);
        self::assertStringContainsString('q=test', $url);
        self::assertStringContainsString('sort=name', $url);
    }

    public function testFragment()
    {
        $result = $this->builder(range(1, 100))
            ->fragment('results')
            ->paginate(page: 1);

        $url = $result->getUrl(2);
        self::assertStringContainsString('#results', $url);
    }

    public function testWithPath()
    {
        $result = $this->builder(range(1, 100))
            ->path('/admin/posts')
            ->paginate(page: 1);

        $url = $result->getUrl(2);
        self::assertStringStartsWith('/admin/posts', $url);
    }

    public function testQueryParam()
    {
        $result = $this->builder(range(1, 100))
            ->pageParameter('p')
            ->paginate(page: 1);

        $url = $result->getUrl(2);
        self::assertStringContainsString('p=2', $url);
        self::assertStringNotContainsString('page=', $url);
    }

    public function testQueryParameterNameCannotBeEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');

        $this->builder([])->pageParameter('');
    }

    public function testImmutability()
    {
        $builder = $this->builder(range(1, 100));
        $modified = $builder->perPage(5);

        // Builder should be immutable
        self::assertNotSame($builder, $modified);

        $result1 = $builder->paginate();
        $result2 = $modified->paginate();

        self::assertSame(20, $result1->getItemsPerPage());
        self::assertSame(5, $result2->getItemsPerPage());
    }

    public function testPaginateThrowsForInvalidPage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder([])->paginate(page: 0);
    }

    public function testDefaultMaximumOffsetAllowsItsBoundary()
    {
        $pagination = $this->builder([])
            ->perPage(20)
            ->paginate(page: 5001);

        self::assertSame(5001, $pagination->getCurrentPage());
    }

    public function testDefaultMaximumOffsetRejectsLargerPagesWithCursorGuidance()
    {
        $this->expectException(OffsetLimitExceededException::class);
        $this->expectExceptionMessage('maximum offset of 100000');
        $this->expectExceptionMessage('$paginator->cursor($source)->orderBy(...)->paginate()');

        $this->builder([])
            ->perPage(20)
            ->paginate(page: 5002);
    }

    public function testMaximumOffsetCanBeRaisedExplicitly()
    {
        $pagination = $this->builder([])
            ->perPage(20)
            ->maxOffset(120_000)
            ->paginate(page: 5002);

        self::assertSame(5002, $pagination->getCurrentPage());
    }

    public function testMaximumOffsetRejectsIntegerOverflow()
    {
        $this->expectException(OffsetLimitExceededException::class);

        $this->builder([])
            ->perPage(2)
            ->maxOffset(\PHP_INT_MAX)
            ->paginate(page: \PHP_INT_MAX);
    }

    public function testMaximumOffsetMustBePositiveOrZero()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('maxOffset must be >= 0.');

        $this->builder([])->maxOffset(-1);
    }

    public function testThrowsForUnsupportedSource()
    {
        $builder = new PaginationBuilder(
            source: new \stdClass(),
            adapters: [new ArrayPaginationAdapter()],
        );

        $this->expectException(\InvalidArgumentException::class);
        $builder->paginate();
    }

    public function testWithTotalInt()
    {
        $result = $this->builder(range(1, 100))
            ->total(50)
            ->paginate(page: 1);

        self::assertSame(50, $result->getTotalItems());
        self::assertSame(3, $result->getTotalPages()); // 50 items / 20 per page = ceil(2.5) = 3 pages
        self::assertCount(20, $result); // Still returns 20 items on the page
    }

    public function testWithTotalCallable()
    {
        $result = $this->builder(range(1, 100))
            ->total(static fn () => 42)
            ->paginate(page: 1);

        self::assertSame(42, $result->getTotalItems());
        self::assertSame(3, $result->getTotalPages()); // 42 items / 20 per page = ceil(2.1) = 3 pages
    }

    public function testWithTotalCallableThrowsForInvalidReturn()
    {
        $result = $this->builder(range(1, 100))
            ->total(static fn () => 'invalid')
            ->paginate(page: 1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total callable must return an int');
        $result->getTotalItems();
    }

    public function testWithTotalRejectsNegativeInteger()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('greater than or equal to 0');

        $this->builder([])->total(-1);
    }

    public function testWithTotalAcceptsInvokableService()
    {
        $counter = new class {
            public function __invoke(): int
            {
                return 73;
            }
        };

        $result = $this->builder(range(1, 10))->total($counter)->paginate();

        self::assertSame(73, $result->getTotalItems());
    }

    public function testWithTotalMaintainsImmutability()
    {
        $builder = $this->builder(range(1, 100));
        $modified = $builder->total(50);

        self::assertNotSame($builder, $modified);

        $result1 = $builder->paginate();
        $result2 = $modified->paginate();

        self::assertSame(100, $result1->getTotalItems());
        self::assertSame(50, $result2->getTotalItems());
    }

    public function testWithTotalCaching()
    {
        $callCount = 0;
        $callable = static function () use (&$callCount) {
            ++$callCount;

            return 42;
        };

        $result = $this->builder(range(1, 100))
            ->total($callable)
            ->paginate(page: 1);

        // Verify that the callable is only invoked once due to caching
        $count1 = $result->getTotalItems();
        $count2 = $result->getTotalItems();

        self::assertSame(42, $count1);
        self::assertSame(42, $count2);
        self::assertSame(1, $callCount);
    }

    public function testConstructorRejectsPerPageOverflow()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('less than PHP_INT_MAX');

        new PaginationBuilder(range(1, 3), [new ArrayPaginationAdapter()], defaultPerPage: \PHP_INT_MAX);
    }

    public function testExposesTheConfiguredPageParameterName()
    {
        $builder = new PaginationBuilder(range(1, 3), [new ArrayPaginationAdapter()]);

        self::assertSame('page', $builder->getPageParameterName());
        self::assertSame('p', $builder->pageParameter('p')->getPageParameterName());
    }

    public function testExposesWhetherAnExplicitRouteWasConfigured()
    {
        $builder = new PaginationBuilder(range(1, 3), [new ArrayPaginationAdapter()]);

        self::assertFalse($builder->hasRoute());
        self::assertTrue($builder->route('items_list')->hasRoute());
    }

    public function testWithQueryStringPreservesRequestParams()
    {
        $request = new \Symfony\Component\HttpFoundation\Request(query: ['q' => 'search', 'sort' => 'name']);
        $request->attributes->set('_route', 'item_list');
        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $requestStack->push($request);

        $builder = new PaginationBuilder(
            source: range(1, 100),
            adapters: [new ArrayPaginationAdapter()],
            requestStack: $requestStack,
        );

        $result = $builder->preserveQueryString()->paginate(page: 1);
        $url = $result->getUrl(2);

        self::assertStringContainsString('q=search', $url);
        self::assertStringContainsString('sort=name', $url);
    }

    public function testWithoutQueryStringDiscardsRequestParams()
    {
        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $requestStack->push(new \Symfony\Component\HttpFoundation\Request(query: ['q' => 'search']));

        $result = $this->builderWithRequest(range(1, 100), $requestStack)
            ->discardQueryString()
            ->paginate(page: 1);

        self::assertSame('/?page=2', $result->getUrl(2));
    }

    public function testWithoutQueryParametersExcludesAndDeduplicatesNames()
    {
        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $requestStack->push(new \Symfony\Component\HttpFoundation\Request(query: [
            'q' => 'search',
            'sort' => 'name',
        ]));

        $builder = $this->builderWithRequest(range(1, 100), $requestStack);
        $modified = $builder
            ->excludeQueryParameters('q')
            ->excludeQueryParameters('q');
        $result = $modified->paginate(page: 1);

        self::assertNotSame($builder, $modified);
        self::assertSame('/?sort=name&page=2', $result->getUrl(2));
    }

    public function testWithoutQueryParametersRejectsEmptyName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');
        $this->builder([])->excludeQueryParameters('');
    }

    public function testRouteWithUrlGenerator()
    {
        $urlGenerator = $this->createStub(\Symfony\Component\Routing\Generator\UrlGeneratorInterface::class);
        $urlGenerator->method('generate')
            ->willReturnCallback(static function (string $route, array $params): string {
                return '/generated/'.$route.'?'.http_build_query($params);
            });

        $builder = new PaginationBuilder(
            source: range(1, 100),
            adapters: [new ArrayPaginationAdapter()],
            urlGenerator: $urlGenerator,
        );

        $result = $builder->route('item_list', ['category' => 'books'])->paginate(page: 1);
        $url = $result->getUrl(2);

        self::assertStringContainsString('item_list', $url);
        self::assertStringContainsString('category=books', $url);
    }

    public function testPaginateResolvesPageFromRequest()
    {
        $request = new \Symfony\Component\HttpFoundation\Request(query: ['page' => '3']);
        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $requestStack->push($request);

        $builder = new PaginationBuilder(
            source: range(1, 100),
            adapters: [new ArrayPaginationAdapter()],
            requestStack: $requestStack,
        );

        $result = $builder->paginate();

        self::assertSame(3, $result->getCurrentPage());
    }

    public function testEmptyRequestDefaultsToFirstPage()
    {
        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $requestStack->push(new \Symfony\Component\HttpFoundation\Request());

        $result = $this->builderWithRequest(range(1, 100), $requestStack)->paginate();

        self::assertSame(1, $result->getCurrentPage());
    }

    public function testExplicitPageDoesNotReadTheRequestStack()
    {
        $requestStack = $this->createMock(\Symfony\Component\HttpFoundation\RequestStack::class);
        $requestStack->expects(self::never())->method('getCurrentRequest');

        $result = $this->builderWithRequest(range(1, 100), $requestStack)
            ->paginate(page: 3);

        self::assertSame(3, $result->getCurrentPage());
    }

    public function testExplicitAdapterIsUsedWithoutDiscovery()
    {
        $adapter = new ArrayPaginationAdapter();
        $result = new PaginationBuilder(
            source: range(1, 10),
            adapters: [],
            adapter: $adapter,
        )->paginate();

        self::assertSame(range(1, 10), $result->getItems());
    }

    public function testPaginateResolvesPageFromCustomParam()
    {
        $request = new \Symfony\Component\HttpFoundation\Request(query: ['p' => '5']);
        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $requestStack->push($request);

        $builder = new PaginationBuilder(
            source: range(1, 100),
            adapters: [new ArrayPaginationAdapter()],
            requestStack: $requestStack,
        );

        $result = $builder->pageParameter('p')->paginate();

        self::assertSame(5, $result->getCurrentPage());
    }

    public function testPaginateResolvesPageFromPathParameter()
    {
        $request = new \Symfony\Component\HttpFoundation\Request();
        $request->attributes->set('_route', 'blog_list');
        $request->attributes->set('page', 4);
        $request->attributes->set('_route_params', ['page' => 4]);

        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $requestStack->push($request);

        $builder = new PaginationBuilder(
            source: range(1, 100),
            adapters: [new ArrayPaginationAdapter()],
            requestStack: $requestStack,
        );

        $result = $builder->paginate();

        self::assertSame(4, $result->getCurrentPage());
    }

    public function testPaginatePathParameterPriorityOverQuery()
    {
        $request = new \Symfony\Component\HttpFoundation\Request(query: ['page' => '2']);
        $request->attributes->set('_route', 'blog_list');
        $request->attributes->set('page', 8);
        $request->attributes->set('_route_params', ['page' => 8]);

        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $requestStack->push($request);

        $builder = new PaginationBuilder(
            source: range(1, 100),
            adapters: [new ArrayPaginationAdapter()],
            requestStack: $requestStack,
        );

        $result = $builder->paginate();

        self::assertSame(8, $result->getCurrentPage());
    }

    public function testPaginatePathParameterWithCustomQueryParam()
    {
        $request = new \Symfony\Component\HttpFoundation\Request();
        $request->attributes->set('_route', 'blog_list');
        $request->attributes->set('p', 6);
        $request->attributes->set('_route_params', ['p' => 6]);

        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $requestStack->push($request);

        $builder = new PaginationBuilder(
            source: range(1, 100),
            adapters: [new ArrayPaginationAdapter()],
            requestStack: $requestStack,
        );

        $result = $builder->pageParameter('p')->paginate();

        self::assertSame(6, $result->getCurrentPage());
    }

    public function testThrowOnOutOfRangeThrowsFromBuilder()
    {
        $this->expectException(OutOfRangePageException::class);

        $this->builder(range(1, 30))
            ->throwOnOutOfRange()
            ->paginate(page: 5);
    }

    public function testThrowOnOutOfRangeDoesNotThrowWhenInRange()
    {
        $result = $this->builder(range(1, 100))
            ->throwOnOutOfRange()
            ->paginate(page: 3);

        self::assertSame(3, $result->getCurrentPage());
    }

    private function builder(array $source): PaginationBuilder
    {
        return new PaginationBuilder(
            source: $source,
            adapters: [new ArrayPaginationAdapter()],
        );
    }

    private function builderWithRequest(array $source, \Symfony\Component\HttpFoundation\RequestStack $requestStack): PaginationBuilder
    {
        return new PaginationBuilder(
            source: $source,
            adapters: [new ArrayPaginationAdapter()],
            requestStack: $requestStack,
        );
    }
}
