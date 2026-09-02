Getting started
===============

This guide paginates a filterable product list. The pagination links keep the
``category`` filter, because UX Pagination preserves request query parameters
by default.

Create the query
----------------

Return an unpaginated ``QueryBuilder`` from the repository. The paginator
applies the offset and limit later::

    // src/Repository/ProductRepository.php
    namespace App\Repository;

    use App\Entity\Product;
    use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
    use Doctrine\ORM\QueryBuilder;
    use Doctrine\Persistence\ManagerRegistry;

    final class ProductRepository extends ServiceEntityRepository
    {
        public function __construct(ManagerRegistry $registry)
        {
            parent::__construct($registry, Product::class);
        }

        public function listQuery(?string $category): QueryBuilder
        {
            $query = $this->createQueryBuilder('product')
                ->orderBy('product.name', 'ASC');

            if (null !== $category && '' !== $category) {
                $query
                    ->andWhere('product.category = :category')
                    ->setParameter('category', $category);
            }

            return $query;
        }
    }

Configure defaults and a named paginator
----------------------------------------

Define recurring pagination settings in the bundle configuration. This keeps
page size and navigation choices out of controllers, while any screen can
still override them through the immutable builder:

.. code-block:: yaml

    # config/packages/ux_pagination.yaml
    ux_pagination:
        items_per_page: 20
        navigation:
            mode: sliding
            size: 5

        paginators:
            products:
                items_per_page: 12

The ``products`` paginator inherits every root option and overrides only the
page size. Symfony exposes it as an autowirable
``PaginatorInterface $productsPaginator`` service argument.

Understand where the page comes from
------------------------------------

Each ``paginate()`` call has exactly one source for its current page. The
paginator resolves that source in this order:

.. code-block:: text

    paginate(page: 3)
      1. receive the explicit PHP integer 3
      2. skip RequestStack completely
      3. validate that 3 is greater than zero
      4. create a Pagination whose current page is 3

    paginate()
      1. receive null as the page argument
      2. obtain the current Request from RequestStack
      3. use the route attribute named "page" when present
      4. otherwise use the query parameter named "page"
      5. otherwise default to 1
      6. let HttpFoundation convert the selected value to an integer
      7. validate that the integer is greater than zero
      8. create a Pagination with that resolved current page

Symfony's parameter bags reject arrays, decimal values, non-numeric strings,
and integer overflows. UX Pagination never sees the raw HTTP value: it
receives the converted integer and enforces the one-based page invariant.

Route attributes take priority, so ``/products/3?page=8`` resolves to page 3.
The router exposes ``3`` as the ``page`` request attribute. The paginator
reads the query parameter only when that route attribute is absent.

Paginate the query
------------------

Call ``paginate()`` without a page argument. The paginator resolves the page
from the current Request and applies the ``products`` settings::

    // src/Controller/ProductController.php
    namespace App\Controller;

    use App\Repository\ProductRepository;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\UX\Pagination\PaginatorInterface;

    final class ProductController extends AbstractController
    {
        public function __construct(
            private readonly PaginatorInterface $productsPaginator,
        ) {
        }

        #[Route('/products', name: 'product_index')]
        public function __invoke(
            Request $request,
            ProductRepository $repository,
        ): Response {
            $category = $request->query->getString('category') ?: null;
            $products = $this->productsPaginator
                ->query($repository->listQuery($category))
                ->throwOnOutOfRange()
                ->paginate();

            return $this->render('product/index.html.twig', [
                'category' => $category,
                'products' => $products,
            ]);
        }
    }

Named paginators use Symfony's standard autowiring aliases. The default
``PaginatorInterface`` can also be injected into a constructor or controller
action; UX Pagination adds no special value resolver. The application keeps
control of the repository query: the bundle never guesses a data source from
a route or entity name.

Render ordinary links
---------------------

Render the list, then the navigation, with the ``ux_pagination()`` Twig
function:

.. code-block:: html+twig

    {# templates/product/index.html.twig #}
    <form method="get">
        <label for="category">Category</label>
        <select id="category" name="category">
            <option value="">All</option>
            <option value="books" {{ category == 'books' ? 'selected' }}>Books</option>
            <option value="tools" {{ category == 'tools' ? 'selected' }}>Tools</option>
        </select>
        <button>Filter</button>
    </form>

    <p>{{ products.info }}</p>

    {% for product in products %}
        <article>{{ product.name }}</article>
    {% else %}
        <p>No product matches this filter.</p>
    {% endfor %}

    {{ ux_pagination(products, show_info: false) }}

This example opts into ``throwOnOutOfRange()``, so ``paginate()`` runs the
exact count query immediately and rejects a page past the total with a 404.
The ``products.info`` access then materializes the current slice to calculate
its displayed range. The following loop reuses that cached slice. Without the
information line, the data query stays lazy until an item-dependent method or
iteration needs it. Without ``throwOnOutOfRange()``, the exact count also stays
lazy until totals or numbered navigation need it.

Follow the runtime
------------------

For the first request, the important work happens in this order:

.. code-block:: text

    GET /products?category=books&page=2
      1. ProductController calls paginate() without a page argument
      2. RequestStack supplies the current Request
      3. no "page" route attribute exists
      4. Request::query converts "2" to the integer 2
      5. PaginationBuilder validates 2 and stores it as the current page
      6. the products profile supplies 12 items and sliding navigation
      7. Pagination stores offset 12 and limit 12 without fetching the slice
      8. throwOnOutOfRange() runs the exact count query
      9. products.info runs the limited data query to calculate its range
     10. Twig iteration reuses the cached slice
     11. PaginationUrlGenerator builds links from the current Request
     12. category=books is preserved and page changes for each link
     13. Twig renders a complete <nav> with ordinary links

Page resolution and link generation are separate steps. The first chooses the
integer stored by ``Pagination``. The second uses the current Request, the
route policy, and the preserved filters to produce URLs for the other pages.

No JavaScript from the bundle runs after this response. The anchors are the
complete interaction. Because they keep real ``href`` values, they stay
available to Turbo or to application-owned JavaScript.

Inspect the rendered HTML
-------------------------

For two pages of filtered results, the default theme produces this navigation
structure (the optional information line and whitespace are omitted):

.. code-block:: html

    <nav aria-label="Pagination"
         class="ux-pagination">
        <ul class="ux-pagination__list">
            <li class="ux-pagination__item">
                <span class="ux-pagination__link ux-pagination__link--disabled">
                    <span aria-hidden="true">&larr;</span> Previous
                </span>
            </li>
            <li class="ux-pagination__item">
                <span class="ux-pagination__link ux-pagination__link--current"
                      aria-current="page">1</span>
            </li>
            <li class="ux-pagination__item">
                <a href="/products?category=books&amp;page=2"
                   class="ux-pagination__link"
                   aria-label="Go to page 2">2</a>
            </li>
            <li class="ux-pagination__item">
                <a href="/products?category=books&amp;page=2"
                   class="ux-pagination__link"
                   aria-label="Next page"
                   rel="next">Next <span aria-hidden="true">&rarr;</span></a>
            </li>
        </ul>
    </nav>

The package's Twig integration tests cover this structure: the navigation
landmark, the current page, and the previous/next relationships are part of
the rendered contract.

Next, choose a pagination strategy in :doc:`strategies`.
