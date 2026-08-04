Symfony UX Pagination
=====================

.. caution::

    **EXPERIMENTAL** This bundle is currently experimental and is likely to
    change, possibly significantly, before its first stable release.

Symfony UX Pagination provides one lazy, request-aware API for paginating
arrays, Doctrine queries and application data sources. It renders accessible
links on the server and does not require a browser-side runtime.

The bundle answers the questions that usually leak into controllers and
templates:

* Who validates ``?page=`` or ``?cursor=``?
* Will the current filters survive the next link?
* Does this screen really need an exact total and its count query?
* Can inserts or deletes move rows while somebody is navigating?
* Can the controls remain ordinary links without a JavaScript lifecycle?

The paginator makes those decisions explicit while keeping one iterable
result for PHP, Twig and JSON. For example::

    // src/Controller/ProductController.php
    namespace App\Controller;

    use App\Repository\ProductRepository;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\UX\Pagination\PaginatorInterface;

    final class ProductController extends AbstractController
    {
        #[Route('/products', name: 'product_index')]
        public function __invoke(
            ProductRepository $repository,
            PaginatorInterface $paginator,
        ): Response {
            $products = $paginator
                ->cursor($repository->createQueryBuilder('product'))
                ->orderBy(['createdAt', 'id'], 'DESC')
                ->perPage(20)
                ->paginate();

            return $this->render('product/index.html.twig', [
                'products' => $products,
            ]);
        }
    }

.. code-block:: html+twig

    {# templates/product/index.html.twig #}
    {% for product in products %}
        <article>{{ product.name }}</article>
    {% endfor %}

    {{ ux_pagination(products) }}

Mental model
------------

The strategy changes the database contract, not the application architecture:

=============================== =============== ===============================
Question                        Choose          What the result can promise
=============================== =============== ===============================
Need exact totals/page numbers? Offset          Counted numbered navigation
Only need a reliable next link? Lookahead       Previous/next, no exact total
Can rows change while browsing? Cursor          Stable sequential traversal
=============================== =============== ===============================

Start here
----------

* :doc:`installation` lists the requirements and installation paths.
* :doc:`getting-started` builds a filtered Doctrine list from scratch.
* :doc:`strategies` helps choose offset, lookahead or cursor pagination.
* :doc:`cursor` explains stable ordering, signed tokens and backward links.

Use it in an application
------------------------

* :doc:`rendering` covers Twig themes, accessibility and browser integration.
* :doc:`doctrine` covers ORM/DBAL queries, counts and database indexes.
* :doc:`live-component` adds server-reactive pagination and shows how to keep
  the page in a route path such as ``/products/{page}``.
* :doc:`integrations` covers APIs, a GitHub GraphQL cursor connection, Turbo
  and third-party UI.
* :doc:`custom-adapters` connects an API, search engine or custom store.
* :doc:`adopting` compares the bundle with an existing pagination solution.
* :doc:`configuration` is the complete bundle and builder reference.

Ship with confidence
--------------------

* :doc:`debugging` diagnoses request, adapter, count and cursor failures.
* :doc:`testing` shows unit, functional and LiveComponent test patterns.
* :doc:`production` covers security, performance and operational limits.

The package supports PHP 8.4 or later, Symfony 7.4 or 8.x, and Twig 3.10.3 or
later in the Twig 3 series.
Doctrine ORM/DBAL, TwigComponent and LiveComponent integrations are optional.
