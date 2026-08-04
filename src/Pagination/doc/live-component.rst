LiveComponent integration
=========================

Use LiveComponent when a filter, a sort order or a page change must
re-render one part of the page from PHP. Ordinary pagination links do not
require it.

``ComponentWithPaginationTrait`` integrates numbered pagination only. Use it
with offset pagination or call ``lookahead()`` on the returned builder. Cursor
pagination has opaque previous and next tokens instead of the integer ``page``
state managed by this trait.

Install the optional integration:

.. code-block:: terminal

    $ composer require symfony/ux-live-component

Configure a named paginator when several component instances share the
same policy:

.. code-block:: yaml

    # config/packages/ux_pagination.yaml
    ux_pagination:
        paginators:
            live_products:
                items_per_page: 5
                navigation:
                    mode: sliding
                    size: 5

Build the pagination
--------------------

Add ``ComponentWithPaginationTrait`` to the component and return a
configured ``PaginationBuilder`` from ``createPagination()``. The trait
stays independent from Doctrine: the application chooses the source
through ``PaginatorInterface``::

    // src/Twig/Components/ProductList.php
    namespace App\Twig\Components;

    use App\Repository\ProductRepository;
    use Symfony\Component\DependencyInjection\Attribute\Target;
    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\Attribute\LiveProp;
    use Symfony\UX\LiveComponent\DefaultActionTrait;
    use Symfony\UX\Pagination\LiveComponent\ComponentWithPaginationTrait;
    use Symfony\UX\Pagination\PaginationBuilder;
    use Symfony\UX\Pagination\PaginatorInterface;

    #[AsLiveComponent]
    final class ProductList
    {
        use DefaultActionTrait;
        use ComponentWithPaginationTrait;

        #[LiveProp(writable: true, url: true, onUpdated: 'onQueryUpdated')]
        public string $query = '';

        public function __construct(
            private readonly ProductRepository $products,
            #[Target('live_products')]
            private readonly PaginatorInterface $paginator,
        ) {
        }

        protected function createPagination(): PaginationBuilder
        {
            return $this->paginator
                ->query($this->products->searchQuery($this->query));
        }

        public function onQueryUpdated(string $previousQuery): void
        {
            $this->resetPage();
        }
    }

The trait declares ``page`` as a writable, URL-synchronized LiveProp and
validates it before creating the builder. The URL parameter follows the
page parameter configured on the builder: a ``pageParameter('p')``
override or a ``page_parameter`` profile setting keeps component URLs and
generated links consistent.

The application owns the page size, through the named profile or an
explicit ``perPage()`` builder override. It also resets the page to 1
when a filter changes.

Follow the page through LiveComponent
-------------------------------------

LiveComponent and the paginator have separate responsibilities.
LiveComponent hydrates the component state; the trait passes that state
to the paginator as an explicit PHP value:

.. code-block:: text

    Initial request: /products?page=2
      1. LiveComponent maps the URL value to the typed LiveProp $page
      2. ProductList::$page now contains the integer 2
      3. getPagination() calls paginate(page: $this->page)
      4. PaginationBuilder receives the explicit integer 2
      5. PaginationBuilder does not consult RequestStack

    Click page 3
      1. the link keeps its ordinary href for non-Live fallback
      2. live#action:prevent calls goToPage(page: 3)
      3. the typed LiveArg supplies the integer 3
      4. goToPage() stores 3 in the LiveProp and clears the result cache
      5. getPagination() calls paginate(page: 3)
      6. the component re-renders page 3
      7. LiveComponent synchronizes the browser URL from the LiveProp

The paginator never tries to rediscover a LiveComponent page from the
AJAX Request. It receives the component's typed state explicitly. The
same rule applies when application code calls ``paginate(page: 3)``
directly.

Links stay on the page URL
--------------------------

LiveComponent re-renders run through internal component routes. The trait
captures the route of the page on the initial render and reuses it for
every generated link. Pagination URLs keep pointing at the page, never at
the internal component endpoint.

Be explicit when you know where the component is rendered. Either call
``route()`` in ``createPagination()``, or pass the page route when
embedding the component:

.. code-block:: html+twig

    <twig:ProductList paginationRoute="product_index" />

Both take precedence over the captured route: ``route()`` wins over
everything, and an explicit ``paginationRoute`` disables the capture.

When the explicit route has required parameters, pass them with
``paginationRouteParams``:

.. code-block:: html+twig

    <twig:ProductList
        paginationRoute="category_products"
        :paginationRouteParams="{category: category.slug}"
    />

The route receives these values in addition to the generated page parameter.
An explicit ``route()`` call in ``createPagination()`` still takes precedence.

Put the page in the route path
------------------------------

The trait maps ``page`` to the query string by default, under the
configured page parameter name, because it cannot assume an application
route. When the route declares a ``{page}`` parameter, redeclare the
compatible property with LiveComponent's path mapping::

    use Symfony\UX\LiveComponent\Attribute\LiveProp;
    use Symfony\UX\LiveComponent\Metadata\UrlMapping;

    #[LiveProp(writable: true, url: new UrlMapping(mapPath: true))]
    public int $page = 1;

Declare the route and pass its initial value to the component::

    #[Route(
        '/products/{page}',
        name: 'product_index',
        requirements: ['page' => '[1-9]\d*'],
    )]
    public function index(int $page): Response
    {
        return $this->render('product/index.html.twig', [
            'page' => $page,
        ]);
    }

.. code-block:: html+twig

    <twig:ProductList :page="page" />

Finally, make the builder generate the same path-based route. This keeps
every ``href`` valid before LiveComponent connects and after a component
re-render::

    protected function createPagination(): PaginationBuilder
    {
        return $this->paginator
            ->query($this->products->searchQuery($this->query))
            ->route('product_index')
            ->queryParameters(['query' => $this->query]);
    }

Live interactions now update ``/products/1`` to ``/products/2`` while
filters such as ``query`` remain in the query string.

With path mapping, the first value comes from the router's ``{page}``
attribute and LiveComponent hydrates the LiveProp from it. Passing
``:page`` also makes that initial ownership explicit in the template. On
later actions, LiveComponent updates the path from the new LiveProp
value. In both cases the paginator only receives the resulting integer.

Render real links with live actions
-----------------------------------

The LiveComponent root must include ``{{ attributes }}``.
``ComponentWithPaginationTrait`` exposes ``paginationLinkAttributes``, a
closure that adds the Live action to each numbered link:

.. code-block:: html+twig

    {# templates/components/ProductList.html.twig #}
    <div {{ attributes }}>
        <label for="product-query">Search</label>
        <input
            id="product-query"
            type="search"
            data-model="debounce(300)|query"
        >

        <div data-loading="addClass(opacity-50)">
            {% for product in this.pagination %}
                <article>{{ product.name }}</article>
            {% else %}
                <p>No matching product.</p>
            {% endfor %}
        </div>

        {{ ux_pagination(
            this.pagination,
            link_attributes: this.paginationLinkAttributes,
        ) }}
    </div>

The closure adds ``live#action:prevent`` and an explicit page argument to
each numbered link. LiveComponent re-renders the component when its
runtime is available. The ``href`` remains valid, so the same markup
performs a normal request if LiveComponent does not connect.

No Live-specific pagination theme is involved. The same integration works
with the default, Bootstrap, Tailwind or an application theme.

Use the actions directly
------------------------

``ComponentWithPaginationTrait`` exposes:

============================== ================================================
Method                         Purpose
============================== ================================================
``goToPage(page)``             Select an explicit one-based page
``nextPage()``                 Advance only when a next page exists
``previousPage()``             Go back without crossing page 1
``resetPage()``                Reset after an application filter or sort change
============================== ================================================

An infinite or cumulative feed has a different state contract. Keep its
accumulated items in the application component instead of using
``ComponentWithPaginationTrait`` as storage.
