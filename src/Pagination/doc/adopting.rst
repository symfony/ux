Adopting UX Pagination
======================

You do not need to replace a working paginator only because this bundle
exists. Adopt UX Pagination when its application-level contract removes
code that your controllers and templates currently own.

Choose it for a new application
-------------------------------

UX Pagination is a strong default when you want:

* one paginator entry point with immutable numbered and cursor builders for
  arrays, Doctrine ORM, Doctrine DBAL and custom sources;
* validated ``page`` and signed ``cursor`` values read from the current
  Request;
* generated URLs that preserve filters and use the Symfony Router;
* lazy totals, lookahead navigation or cursor traversal selected
  explicitly;
* one iterable result for PHP, Twig and JSON;
* accessible server-rendered links without a JavaScript runtime;
* named paginator policies injectable through the service container;
* test helpers that exercise real URL and cursor behavior.

The bundle is not another way to calculate ``LIMIT`` and ``OFFSET``. It
owns the complete boundary between the Request, the data-source
strategy, the pagination result and its URLs.

Keep an existing solution
-------------------------

Keeping the current paginator is reasonable when:

* it already expresses the required query, URL and rendering contracts;
* the application relies on extension points that UX Pagination does not
  provide;
* cursor traversal is not needed and migrating would only rename
  familiar methods;
* a third-party bundle or administration system integrates directly with
  the current paginator;
* the team cannot test every existing pagination URL during the
  migration.

Cursor pagination alone can justify adopting UX Pagination for new
high-volume or frequently changing feeds. It does not require converting
every numbered list in the application at the same time.

Map the concepts
----------------

================================== ==============================================
Existing application concept       UX Pagination concept
================================== ==============================================
Pagination service                 ``PaginatorInterface``
Page size                          ``items_per_page`` or ``perPage()``
Current page from the Request      Resolved automatically, or ``paginate(page:)``
Paginated result                   ``PaginationInterface``
Total and final page               ``NumberedPaginationInterface``
Previous/next cursor               ``CursorPaginationInterface``
Template or view                   ``ux_pagination()`` and a Twig template
Reusable paginator configuration   A named paginator
Custom data-source integration     A tagged adapter
================================== ==============================================

Migrate one list
----------------

Start with one controller. Keep the route and the query parameter
unchanged::

    // src/Controller/ProductController.php
    $products = $paginator
        ->query($repository->createQueryBuilder('product'))
        ->perPage(20)
        ->paginate();

Then render the result directly:

.. code-block:: html+twig

    {# templates/product/index.html.twig #}
    {% for product in products %}
        <article>{{ product.name }}</article>
    {% endfor %}

    {{ ux_pagination(products) }}

Before replacing the existing implementation, test:

* first, middle, final and out-of-range pages;
* every filter and sort query parameter;
* generated absolute URLs if feeds or APIs consume them;
* custom templates and translated labels;
* the number of data and count queries.

Once the numbered list behaves identically, decide separately whether it
benefits from lookahead or cursor navigation. A migration should not
silently change the product's navigation model.
