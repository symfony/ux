Testing pagination
==================

UX Pagination's public contracts need no browser to test. The bundle ships
``PaginatorFactory``, a small factory that wires the real array adapter,
request handling, URL generation, and signed cursor codec. The factory has no
PHPUnit dependency.

Test helper
-----------

``PaginatorFactory::create()`` returns the real ``Paginator`` with
deterministic test defaults. Pass named options only for the behavior under
test:

``request``
    A ``Request`` used to resolve an omitted current page or cursor and to
    generate links. An explicit ``paginate(page: ...)`` value bypasses request
    page resolution. The Request defaults to ``/``.

``requestStack``
    An existing ``RequestStack``. When both options are passed, ``request`` is
    pushed as its current request.

``urlGenerator``
    A real ``UrlGeneratorInterface`` for named or path-parameter routes.

``defaultPerPage``, ``defaultPageParam``, ``defaultCursorParam``, ``defaultMaxOffset``
    The corresponding paginator defaults.

``cursorSecret``
    A deterministic signing secret. Override it when a test needs isolated
    cursor domains.

.. caution::

    ``PaginatorFactory`` belongs in application tests. Its default cursor
    secret is intentionally predictable and must never configure the
    production service.

Unit-test offset pagination and URLs
------------------------------------

Assert the slice, the totals, and the generated URLs from one ``Request``::

    // tests/Pagination/ProductPaginationTest.php
    namespace App\Tests\Pagination;

    use PHPUnit\Framework\TestCase;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\UX\Pagination\Test\PaginatorFactory;

    final class ProductPaginationTest extends TestCase
    {
        public function testSecondPageKeepsTheExpectedSlice(): void
        {
            $paginator = PaginatorFactory::create(
                request: Request::create(
                    'https://example.test/products?category=tools&page=2',
                ),
                defaultPerPage: 10,
            );

            $page = $paginator->paginate(range(1, 25));

            self::assertSame(range(11, 20), $page->getItems());
            self::assertSame(25, $page->getTotalItems());
            self::assertTrue($page->hasPrevious());
            self::assertTrue($page->hasNext());
            self::assertSame(
                '/products?category=tools&page=3',
                $page->getNextUrl(),
            );
        }
    }

Functional-test the generated links
-----------------------------------

Assert that rendered links preserve the active filter::

    // tests/Controller/ProductControllerTest.php
    namespace App\Tests\Controller;

    use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

    final class ProductControllerTest extends WebTestCase
    {
        public function testCategorySurvivesNavigation(): void
        {
            $client = self::createClient();
            $crawler = $client->request('GET', '/products?category=tools');

            self::assertResponseIsSuccessful();
            $next = $crawler->selectLink('Next')->link()->getUri();
            self::assertStringContainsString('category=tools', $next);
            self::assertStringContainsString('page=2', $next);
        }
    }

Cursor round trip
-----------------

Exercise the signed token, the query string, and the array adapter together::

    // tests/Pagination/EventPaginationTest.php
    namespace App\Tests\Pagination;

    use PHPUnit\Framework\TestCase;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\UX\Pagination\Test\PaginatorFactory;

    final class EventPaginationTest extends TestCase
    {
        public function testCursorRoundTrip(): void
        {
            $events = array_map(
                static fn (int $id): array => ['id' => $id],
                range(1, 6),
            );

            $first = PaginatorFactory::create(
                request: Request::create('/events?type=release'),
            )
                ->cursor($events)
                ->orderBy('id', 'ASC')
                ->perPage(2)
                ->context('event-feed')
                ->paginate();

            $nextUrl = $first->getNextUrl();
            self::assertNotNull($nextUrl);

            $second = PaginatorFactory::create(
                request: Request::create(
                    'https://example.test'.$nextUrl,
                ),
            )
                ->cursor($events)
                ->orderBy('id', 'ASC')
                ->perPage(2)
                ->context('event-feed')
                ->paginate();

            self::assertSame(
                [3, 4],
                array_column($second->getItems(), 'id'),
            );

            $previousUrl = $second->getPreviousUrl();
            self::assertNotNull($previousUrl);

            $again = PaginatorFactory::create(
                request: Request::create(
                    'https://example.test'.$previousUrl,
                ),
            )
                ->cursor($events)
                ->orderBy('id', 'ASC')
                ->perPage(2)
                ->context('event-feed')
                ->paginate();

            self::assertSame(
                [1, 2],
                array_column($again->getItems(), 'id'),
            );
        }
    }

For Doctrine, also create rows that share the primary ordered value. This
proves that the configured cursor order and its unique tie-breaker prevent
duplicates and gaps::

    // tests/Controller/EventControllerTest.php
    $first = $client->request('GET', '/events');
    $next = $first->selectLink('Next')->link()->getUri();

    $second = $client->request('GET', $next);
    self::assertCount(10, $second->filter('[data-event-id]'));

    $previous = $second->selectLink('Previous')->link()->getUri();
    $again = $client->request('GET', $previous);

    self::assertSame(
        $first->filter('[data-event-id]')->extract(['data-event-id']),
        $again->filter('[data-event-id]')->extract(['data-event-id']),
    );

Also cover these cases:

* a changed filter;
* an invalid token;
* the first page and the final page;
* backward navigation from the final page.

LiveComponent
-------------

UX LiveComponent provides the ``InteractsWithLiveComponents`` helper used
below. Add it to a ``KernelTestCase`` to exercise the same request cycle as
the browser. This example uses the ``ProductList`` component from
:doc:`live-component`, configured for five items per page, and assumes exactly
eight fixtures matching ``Books``::

    // tests/Twig/Components/ProductListTest.php
    namespace App\Tests\Twig\Components;

    use App\Twig\Components\ProductList;
    use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
    use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

    final class ProductListTest extends KernelTestCase
    {
        use InteractsWithLiveComponents;

        public function testItFiltersAndMovesToTheNextPage(): void
        {
            $component = $this->createLiveComponent(ProductList::class);

            $component
                ->set('query', 'Books')
                ->call('nextPage')
            ;

            self::assertSame(2, $component->component()->page);

            $rendered = $component->render();
            self::assertCount(
                3,
                $rendered->crawler()->filter('[data-product-id]'),
            );
        }
    }

Keep repository fixtures small and deterministic. Adapt the selector and the
expected count to the component's own markup and fixtures. Here, the page
state and the three rendered rows prove that ``nextPage`` re-ran the filtered
server query for the second page.

Browser coverage
----------------

Add one browser test for native previous/next links, current-page semantics,
and filter preservation. For LiveComponent integration, add a second test
that proves a page action re-renders the component while the same ``href``
still works without LiveComponent.
