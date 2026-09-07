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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\UX\Pagination\Adapter\ArrayPaginationAdapter;
use Symfony\UX\Pagination\Adapter\CursorAdapterInterface;
use Symfony\UX\Pagination\Adapter\DoctrineDbalAdapter;
use Symfony\UX\Pagination\Adapter\PaginationAdapterInterface;
use Symfony\UX\Pagination\Cursor\CursorBoundary;
use Symfony\UX\Pagination\Cursor\CursorCodec;
use Symfony\UX\Pagination\Cursor\CursorOrder;
use Symfony\UX\Pagination\Cursor\CursorSlice;
use Symfony\UX\Pagination\CursorPaginationBuilder;
use Symfony\UX\Pagination\Exception\InvalidCursorException;
use Symfony\UX\Pagination\Exception\RuntimeException;
use Symfony\UX\Pagination\Paginator;

#[CoversClass(CursorPaginationBuilder::class)]
final class CursorPaginationBuilderTest extends TestCase
{
    public function testArraySourceRequiresExplicitContext(): void
    {
        $this->expectException(RuntimeException::class);
        $this->paginator()->cursor($this->source())->paginate();
    }

    public function testBuildsSignedBidirectionalPagination(): void
    {
        $page1 = $this->paginator()->cursor($this->source())
            ->orderBy('id', 'ASC')
            ->perPage(5)
            ->context('products')
            ->paginate();

        self::assertSame(range(1, 5), array_column($page1->getItems(), 'id'));
        self::assertNotNull($page1->getNextCursor());

        $page2 = $this->paginator()->cursor($this->source())
            ->orderBy('id', 'ASC')
            ->perPage(5)
            ->context('products')
            ->cursor($page1->getNextCursor())
            ->paginate();

        self::assertSame(range(6, 10), array_column($page2->getItems(), 'id'));
        self::assertNotNull($page2->getPreviousCursor());
    }

    #[DataProvider('invalidOrderByArguments')]
    public function testRejectsInvalidOrderByArguments(string|array $fields, string $direction): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->paginator()->cursor($this->source())->orderBy($fields, $direction);
    }

    public static function invalidOrderByArguments(): iterable
    {
        yield 'no fields' => [[], 'ASC'];
        yield 'empty field' => [['id', ''], 'ASC'];
        yield 'invalid direction' => ['id', 'sideways'];
    }

    public function testRejectsInvalidPageSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('perPage must be >= 1');

        $this->paginator()->cursor($this->source())->perPage(0);
    }

    public function testRejectsPageSizeThatWouldOverflowLookahead(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('less than PHP_INT_MAX');

        $this->paginator()->cursor($this->source())->perPage(\PHP_INT_MAX);
    }

    public function testRejectsEmptyCursorParameter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cursor parameter name must not be empty');

        $this->paginator()->cursor($this->source())->cursorParameter('');
    }

    public function testConstructorRejectsPerPageOverflow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('less than PHP_INT_MAX');

        new CursorPaginationBuilder(
            $this->source(),
            [new ArrayPaginationAdapter()],
            new CursorCodec('test-application-secret'),
            defaultPerPage: \PHP_INT_MAX,
        );
    }

    public function testRejectsEmptyContext(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cursor context must not be empty');

        $this->paginator()->cursor($this->source())->context('');
    }

    public function testTokenIsBoundToBusinessContext(): void
    {
        $token = $this->paginator()->cursor($this->source())
            ->orderBy('id', 'ASC')
            ->perPage(5)
            ->context('tenant-a:products')
            ->paginate()
            ->getNextCursor();

        $builder = $this->paginator()->cursor($this->source())
            ->orderBy('id', 'ASC')
            ->perPage(5)
            ->context('tenant-b:products')
            ->cursor($token);

        $this->expectException(InvalidCursorException::class);
        $builder->paginate();
    }

    public function testUrlCompositionPreservesFiltersAndCustomCursorParameter(): void
    {
        $stack = new RequestStack();
        $stack->push(Request::create('/catalog', 'GET', ['q' => 'phone', 'sort' => 'price']));

        $pagination = $this->paginator($stack)->cursor($this->source())
            ->orderBy('id', 'ASC')
            ->perPage(5)
            ->context('products')
            ->cursorParameter('after')
            ->path('/products')
            ->queryParameters(['sort' => 'name'])
            ->fragment('results')
            ->paginate();

        $url = (string) $pagination->getNextUrl();
        self::assertStringStartsWith('/products?', $url);
        self::assertStringContainsString('q=phone', $url);
        self::assertStringContainsString('sort=name', $url);
        self::assertStringContainsString('after=', $url);
        self::assertStringEndsWith('#results', $url);
        self::assertStringNotContainsString('cursor=', $url);
    }

    public function testRouteAndQueryStringPoliciesAreImmutable(): void
    {
        $routes = new RouteCollection();
        $routes->add('catalog', new Route('/{section}'));
        $urlGenerator = new UrlGenerator($routes, new RequestContext());
        $stack = new RequestStack();
        $stack->push(Request::create('/source', 'GET', ['q' => 'phone', 'debug' => '1']));
        $paginator = new Paginator(
            [new ArrayPaginationAdapter()],
            requestStack: $stack,
            urlGenerator: $urlGenerator,
            cursorCodec: new CursorCodec('test-application-secret'),
        );
        $builder = $paginator->cursor($this->source())
            ->orderBy('id', 'ASC')
            ->perPage(5)
            ->context('products')
            ->route('catalog', ['section' => 'products']);

        $discardedUrl = (string) $builder
            ->discardQueryString()
            ->paginate()
            ->getNextUrl();
        $preservedUrl = (string) $builder
            ->discardQueryString()
            ->preserveQueryString()
            ->excludeQueryParameters('debug', 'debug')
            ->paginate()
            ->getNextUrl();

        self::assertStringStartsWith('/products?', $discardedUrl);
        self::assertStringNotContainsString('q=', $discardedUrl);
        self::assertStringContainsString('q=phone', $preservedUrl);
        self::assertStringNotContainsString('debug=', $preservedUrl);
    }

    public function testReadsAValidCursorFromTheRequest(): void
    {
        $firstPage = $this->paginator()->cursor($this->source())
            ->orderBy('id', 'ASC')
            ->perPage(5)
            ->context('products')
            ->paginate();
        $cursor = $firstPage->getNextCursor();
        self::assertNotNull($cursor);

        $stack = new RequestStack();
        $stack->push(Request::create('/catalog', 'GET', ['cursor' => $cursor]));
        $secondPage = $this->paginator($stack)->cursor($this->source())
            ->orderBy('id', 'ASC')
            ->perPage(5)
            ->context('products')
            ->paginate();

        self::assertSame($cursor, $secondPage->getCursor());
        self::assertSame(range(6, 10), array_column($secondPage->getItems(), 'id'));
    }

    #[DataProvider('invalidRequestCursors')]
    public function testRejectsInvalidRequestCursor(mixed $value): void
    {
        $stack = new RequestStack();
        $stack->push(new Request(['cursor' => $value]));

        $this->expectException(InvalidCursorException::class);
        $this->paginator($stack)->cursor($this->source())
            ->context('products')
            ->paginate();
    }

    public static function invalidRequestCursors(): iterable
    {
        yield 'empty' => [''];
        yield 'array' => [['token']];
        yield 'oversized' => [str_repeat('a', 4097)];
    }

    public function testTamperedRequestCursorFailsAtPaginateTime(): void
    {
        $token = $this->paginator()->cursor($this->source())
            ->orderBy('id', 'ASC')
            ->perPage(5)
            ->context('products')
            ->paginate()
            ->getNextCursor();
        self::assertNotNull($token);

        $stack = new RequestStack();
        $stack->push(Request::create('/catalog', 'GET', ['cursor' => substr($token, 0, -2)]));

        $this->expectException(InvalidCursorException::class);
        $this->paginator($stack)->cursor($this->source())
            ->orderBy('id', 'ASC')
            ->perPage(5)
            ->context('products')
            ->paginate();
    }

    public function testMalformedSignedTokenUsesDomainException(): void
    {
        $builder = $this->paginator()->cursor($this->source())
            ->orderBy('id')
            ->context('products')
            ->cursor('not-a-token');

        $this->expectException(InvalidCursorException::class);
        $builder->paginate();
    }

    public function testDoctrineDbalSourceDerivesContextFromSqlAndParameters(): void
    {
        if (!class_exists(\Doctrine\DBAL\DriverManager::class)) {
            self::markTestSkipped('Doctrine DBAL is not installed.');
        }

        $connection = \Doctrine\DBAL\DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $connection->executeStatement('CREATE TABLE product (id INTEGER PRIMARY KEY, category TEXT NOT NULL)');
        foreach (range(1, 6) as $id) {
            $connection->insert('product', ['id' => $id, 'category' => $id < 5 ? 'books' : 'tools']);
        }

        $query = static fn (string $category) => $connection->createQueryBuilder()
            ->select('product.id', 'product.category')
            ->from('product', 'product')
            ->where('product.category = :category')
            ->setParameter('category', $category);

        $paginator = new Paginator(
            [new DoctrineDbalAdapter()],
            cursorCodec: new CursorCodec('test-application-secret'),
        );
        $first = $paginator->cursor($query('books'))
            ->orderBy('product.id', 'ASC')
            ->perPage(2)
            ->paginate();

        self::assertSame([1, 2], array_column($first->getItems(), 'id'));
        $cursor = $first->getNextCursor();
        self::assertNotNull($cursor);

        $changedQuery = $paginator->cursor($query('tools'))
            ->orderBy('product.id', 'ASC')
            ->perPage(2)
            ->cursor($cursor);

        $this->expectException(InvalidCursorException::class);
        $changedQuery->paginate();
    }

    public function testExcludedQueryParameterNameCannotBeEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');

        $this->paginator()->cursor($this->source())->excludeQueryParameters('');
    }

    public function testRejectsAnAdapterWithoutCursorSupport(): void
    {
        $adapter = $this->createStub(PaginationAdapterInterface::class);
        $adapter->method('supports')->willReturn(true);
        $paginator = new Paginator(
            [$adapter],
            cursorCodec: new CursorCodec('test-application-secret'),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No cursor pagination adapter found');

        $paginator->cursor($this->source())->context('products')->paginate();
    }

    public function testRejectsAnUnsupportedSource(): void
    {
        $paginator = new Paginator(
            [new ArrayPaginationAdapter()],
            cursorCodec: new CursorCodec('test-application-secret'),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No cursor pagination adapter found');

        $paginator->cursor(new \stdClass())->context('products')->paginate();
    }

    public function testAdapterOwnedOrderDoesNotRequireOrderBy(): void
    {
        $source = new \stdClass();
        $adapter = new class implements CursorAdapterInterface {
            public function supports(mixed $source): bool
            {
                return $source instanceof \stdClass;
            }

            public function resolveCursorOrder(mixed $source, ?array $fields, ?string $direction): CursorOrder
            {
                if (null !== $fields || null !== $direction) {
                    throw new \LogicException('The remote adapter must own its order.');
                }

                return CursorOrder::byIdentity('github:pull-requests:created-desc');
            }

            public function getCursorContext(mixed $source, ?string $context): string
            {
                return 'github:owner/repository:pull-requests';
            }

            public function sliceWithCursor(mixed $source, ?CursorBoundary $boundary, int $limit, CursorOrder $order): CursorSlice
            {
                return new CursorSlice([['number' => 1]], null, null, false);
            }
        };
        $paginator = new Paginator(
            [$adapter],
            cursorCodec: new CursorCodec('test-application-secret'),
        );

        $pagination = $paginator->cursor($source)->paginate();

        self::assertSame([['number' => 1]], $pagination->getItems());
    }

    public function testCursorResolutionSkipsAMatchingAdapterWithoutCursorCapability(): void
    {
        $source = new \stdClass();
        $genericAdapter = $this->createStub(PaginationAdapterInterface::class);
        $genericAdapter->method('supports')->willReturn(true);
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
                return new CursorSlice([['id' => 42]], null, null, false);
            }
        };
        $paginator = new Paginator(
            [$genericAdapter, $cursorAdapter],
            cursorCodec: new CursorCodec('test-application-secret'),
        );

        self::assertSame([['id' => 42]], $paginator->cursor($source)->paginate()->getItems());
    }

    private function paginator(?RequestStack $requestStack = null): Paginator
    {
        return new Paginator(
            [new ArrayPaginationAdapter()],
            requestStack: $requestStack,
            cursorCodec: new CursorCodec('test-application-secret'),
        );
    }

    /**
     * @return list<array{id: int}>
     */
    private function source(): array
    {
        return array_map(static fn (int $id): array => ['id' => $id], range(1, 12));
    }
}
