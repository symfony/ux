Configuration reference
=======================

Bundle configuration
--------------------

.. code-block:: yaml

    # config/packages/ux_pagination.yaml
    ux_pagination:
        items_per_page: 20
        max_offset: 100000
        page_parameter: page
        cursor_parameter: cursor

        navigation:
            mode: sliding
            size: 5

        theme: '@UXPagination/theme/default.html.twig'

        cursor:
            # Optional: defaults to kernel.secret
            secret: '%env(UX_PAGINATION_CURSOR_SECRET)%'

        paginators:
            blog:
                items_per_page: 12
                navigation:
                    size: 7

==================== =========== ================= =========================
Option               Type        Default           Purpose
==================== =========== ================= =========================
``items_per_page``   integer     ``20``            Default page size
``max_offset``       integer     ``100000``        Largest allowed offset
``page_parameter``   string      ``page``          Numbered page parameter
``cursor_parameter`` string      ``cursor``        Cursor parameter
``navigation.mode``  string      ``sliding``       Numbered navigation mode
``navigation.size``  integer     ``5``             Mode size or safety limit
``theme``            string      default template  Pagination theme
``cursor.secret``    string      ``kernel.secret`` Cursor signature secret
``paginators``       map         ``{}``            Named paginator profiles
==================== =========== ================= =========================

The page size is application-owned. Define its default with
``items_per_page`` or select it for a use case with ``perPage()``. UX
Pagination does not read a visitor-controlled page-size query parameter.

Set ``cursor.secret`` when cursor links need an independent rotation policy.
Leave the option unset to reuse ``kernel.secret``. Do not configure ``null``
or an empty value: cursor pagination requires a non-empty signing secret.
Changing the secret invalidates existing cursor URLs.

``navigation.size`` depends on the mode: the moving window size for
``sliding``, the block size for ``fixed``, and the maximum accepted page
count for ``full``.

``theme`` is a complete Twig template name. It defaults to
``@UXPagination/theme/default.html.twig``. A theme passed directly to the
Twig function or component overrides it for that rendering only. Pass one
template, not a list. Compose application themes with Twig inheritance.

Named paginators
----------------

Each entry under ``paginators`` inherits the root page, URL and navigation
settings, then applies only its explicit overrides:

.. code-block:: yaml

    # config/packages/ux_pagination.yaml
    ux_pagination:
        items_per_page: 20
        navigation:
            mode: sliding
            size: 5

        paginators:
            blog:
                items_per_page: 12
                navigation:
                    size: 7

            admin:
                items_per_page: 50
                navigation:
                    mode: fixed
                    size: 10

Inject the profile with a named argument::

    // src/Controller/BlogController.php
    public function __construct(
        private readonly PaginatorInterface $blogPaginator,
    ) {
    }

Use ``#[Target]`` when the argument name should describe its application
role::

    // src/Controller/BlogController.php
    use Symfony\Component\DependencyInjection\Attribute\Target;

    public function __construct(
        #[Target('blog')]
        private readonly PaginatorInterface $paginator,
    ) {
    }

The explicit service escape hatch is ``ux_pagination.paginator.<name>``::

    // src/Controller/BlogController.php
    use Symfony\Component\DependencyInjection\Attribute\Autowire;

    public function __construct(
        #[Autowire(service: 'ux_pagination.paginator.blog')]
        private readonly PaginatorInterface $paginator,
    ) {
    }

Paginator names accept letters, digits, dots, dashes and underscores, but must
start with a letter or underscore. Names that resolve to the same autowiring
target are rejected.

``theme`` and ``cursor.secret`` stay global. The theme belongs to
the renderer, and the signing secret is an application security invariant
rather than a paginator preference.

Application entry points
------------------------

Inject ``PaginatorInterface`` and choose the smallest entry point that exposes
the policy the application needs:

=============================== ================================================
Method                          Use
=============================== ================================================
``paginate(mixed, ?int, ?int)`` Numbered pagination with configured defaults
``query(mixed)``                Numbered builder for navigation, total and URLs
``fromCallbacks(...)``          Numbered builder for an offset slice and count
                                supplied by an API or custom data source
``cursor(mixed)``               Cursor builder with explicit ordering and scope
=============================== ================================================

``paginate()`` is the one-line application default::

    // src/Controller/ProductController.php
    $products = $paginator->paginate($repository->createListQuery());

The other entry points return immutable builders and end with
``->paginate()``. Cursor pagination intentionally has no argument-heavy
shortcut: ordering, page size, explicit cursor and application context remain
named configuration steps.

Builder reference
-----------------

Every builder method is immutable and returns a clone. Configure navigation
policy and URL composition on the builder, then call ``paginate()``.

=============================== ================================================
Method                          Effect
=============================== ================================================
``perPage(int)``                Set the page size
``sliding(int)``                Consecutive page window of the requested size
``fixed(int)``                  Fixed-size page block
``full(int)``                   Every page, guarded by a maximum
``lookahead()``                 Fetch N+1 rows and skip the count
``total(int|callable)``         Known or lazily computed exact total
``pageParameter(string)``       Override the page request parameter
``route(string, array)``        Generate links for another named route
``queryParameters(array)``      Append parameters to every generated URL
``preserveQueryString()``       Preserve Request query parameters (default)
``discardQueryString()``        Discard Request query parameters
``excludeQueryParameters(...)`` Exclude named Request parameters
``fragment(string)``            Add a URL fragment
``path(string)``                Use a custom path
``maxOffset(int)``              Override the configured offset limit
``throwOnOutOfRange()``         Run the count and throw a 404 past the last page
=============================== ================================================

Choose the total strategy explicitly:

=============================== ================================================
Need                            API
=============================== ================================================
Adapter can count the source    ``paginate()`` or ``query(...)->paginate()``
Known or custom exact total     ``query(...)->total(...)->paginate()``
No total needed                 ``query(...)->lookahead()->paginate()``
=============================== ================================================

``total()`` accepts a non-negative integer or a callable returning one. The
callable runs lazily and at most once, so an invokable Symfony service can be
passed directly, without a bundle-specific provider interface.

Do not combine ``lookahead()`` with ``total()`` or ``throwOnOutOfRange()``.
Lookahead deliberately avoids the exact total those policies need, and the
builder rejects both combinations.

URL methods configure the builder, not an already-created result. This keeps
one URL policy for the complete pagination lifecycle::

    // src/Controller/ProductController.php
    $products = $paginator
        ->query($repository->createFilteredQuery($filters))
        ->excludeQueryParameters('debug')
        ->queryParameters(['category' => $category->getSlug()])
        ->fragment('results')
        ->paginate();

Cursor builder reference
------------------------

=================================== ================================================
Method                              Effect
=================================== ================================================
``orderBy(string|array, string)``   Field order for array/Doctrine adapters;
                                    optional for adapter-owned remote orders
``perPage(int)``                    Set the page size
``cursor(?string)``                 Override automatic Request resolution
``cursorParameter(string)``         Override the cursor request parameter
``context(string)``                 Bind tokens to an application boundary
``route(string, array)``            Generate links for another route
``queryParameters(array)``          Append parameters to links
``preserveQueryString()``           Preserve Request query parameters (default)
``discardQueryString()``            Discard Request query parameters
``excludeQueryParameters(...)``     Exclude named parameters
``fragment(string)``                Add a fragment
``path(string)``                    Use a custom path
=================================== ================================================

Result reference
----------------

All pagination results implement ``PaginationInterface``. The result
interfaces intentionally expose only the state and links consumers need for
iteration, rendering and serialization. Application services can type against
``PaginationInterface`` when they only iterate items or expose adjacent
navigation:

================================= ==============================================
Method                            Result
================================= ==============================================
``getItems()``                    Items on the current slice
``count()``                       Number of items on the current slice
``isEmpty()``                     Whether the current slice has no items
``getItemsPerPage()``             Configured page size
``hasPrevious()``                 Whether a previous URL is available
``hasNext()``                     Whether a next URL is available
``getPreviousUrl()``              Nullable previous URL
``getNextUrl()``                  Nullable next URL
``getInfo()``                     Translated result summary
``jsonSerialize()``               Strategy-specific JSON representation
================================= ==============================================

Numbered and lookahead results implement ``NumberedPaginationInterface``:

============================ ============================================
Method                       Result
============================ ============================================
``getCurrentPage()``         Current one-based page
``getPageParameterName()``   Request parameter used for the current page
``getTotalItems()``          Exact total, or ``null`` for lookahead
``getTotalPages()``          Exact page count, or ``null`` for lookahead
``getFirstItemNumber()``     First one-based item position, or ``null``
``getLastItemNumber()``      Last one-based item position, or ``null``
``getUrl(int)``              URL for a numbered page
``getFirstUrl()``            First-page URL
``getLastUrl()``             Final-page URL, or ``null`` without a total
``getPages()``               Navigation links and gaps
``isFirst()`` / ``isLast()`` Boundary state
``isOutOfRange()``           Whether the requested page exceeds the total
============================ ============================================

Cursor results implement ``CursorPaginationInterface``:

================================= ==============================================
Method                            Result
================================= ==============================================
``getCursor()``                   Opaque cursor that produced the current slice
``getNextCursor()``               Nullable next opaque cursor
``getPreviousCursor()``           Nullable previous opaque cursor
``getCursorUrl(string)``          URL for an opaque cursor
================================= ==============================================

Concrete result conveniences
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The concrete ``Pagination`` and ``CursorPagination`` classes also expose
immutable conveniences that are intentionally absent from the shared result
interfaces:

=============================== ================================================
Method                          Effect
=============================== ================================================
``map(callable)``               Transform the current items into a new result
``throwOnOutOfRange()``         Throw a 404 for an invalid numbered result
``getAbsoluteUrl(int)``         Absolute URL for a numbered page
``getMetadata()``               Pagination metadata for API formats
``getLinks()``                  API URLs (first/last/prev/next, or prev/next)
=============================== ================================================

``map()`` and ``getLinks()`` are available on both concrete result classes.
``throwOnOutOfRange()``, ``getAbsoluteUrl()`` and ``getMetadata()`` are
available on ``Pagination`` only. Prefer the builder method for the
out-of-range policy so it is visible before the result is created.

Twig exposes conventional method access as properties. ``pagination.nextUrl``
is common to every result, while ``pagination.currentPage`` and
``pagination.totalItems`` belong to numbered results. Only access
strategy-specific properties when the caller guarantees that result type, and
guard nullable values with ``hasNext``, ``hasPrevious`` or an explicit
``null`` check.

Twig function
-------------

The function has an explicit signature:

.. code-block:: twig

    ux_pagination(
        pagination,
        attributes = {},
        theme = null,
        show_info = true,
        navigation_attributes = {},
        link_attributes = {},
    )

``attributes`` apply to the root ``<nav>``, ``navigation_attributes`` apply
to the controls container, and ``link_attributes`` apply to every real link.
``link_attributes`` also accepts a closure for link-specific attributes. See
:doc:`rendering` for the closure context and the escaping rules.

.. code-block:: twig

    {{ ux_pagination(
        products,
        {
            id: 'product-pages',
            class: 'product-pagination',
            'aria-label': 'Product results',
        },
        theme: '@UXPagination/theme/tailwind.html.twig',
        show_info: false,
        navigation_attributes: {class: 'product-pagination__controls'},
        link_attributes: {class: 'product-pagination__link'},
    ) }}

The Twig component exposes the same contract with normal component
attributes:

.. code-block:: html+twig

    <twig:ux:pagination
        :pagination="products"
        theme="@UXPagination/theme/tailwind.html.twig"
        :showInfo="false"
        id="product-pages"
        class="product-pagination"
        :navigationAttributes="{class: 'product-pagination__controls'}"
        :linkAttributes="{class: 'product-pagination__link'}"
    />

UX Pagination ships no browser runtime. Generic HTML and ``data-*``
attributes remain available for application integrations, without a second
bundle-specific options language. The bundle implements no prefetch policy.
Applications using Turbo can rely on Turbo's prefetch facilities.
