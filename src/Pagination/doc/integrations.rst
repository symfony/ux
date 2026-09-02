Integrations
============

UX Pagination connects to Turbo, CSS frameworks, web APIs, and external data
sources without giving up its navigation state. This page shows which boundary
to adapt in each case.

Choose the integration boundary
-------------------------------

Keep pagination state in the bundle and adapt only the boundary owned by the
third-party system:

============================  ==========================  ==========================
Integration need              Use                         Keep in the application
============================  ==========================  ==========================
Partial page navigation       Turbo Frame                 Frame response boundary
Reactive filters              LiveComponent trait         Component filter state
Bootstrap or Tailwind markup  Built-in theme              Design tokens and layout
Application design system     Theme override              Markup and CSS conventions
HTTP or search data source    Callable or custom adapter  Authentication and retries
JSON API response             ``JsonSerializable``        Resource serialization
============================  ==========================  ==========================

In every case, let the pagination result own navigation state and URL
composition. Do not duplicate page arithmetic or cursor decoding in a
template, a component, or an HTTP client.

Turbo
-----

UX Pagination does not install or require Turbo. When the application already
uses Turbo Drive, the built-in links work without special markup. To update
only a result region, place the list and its navigation in a Turbo Frame:

.. code-block:: html+twig

    {# templates/product/index.html.twig #}
    <turbo-frame id="products">
        {% for product in products %}
            <article>{{ product.name }}</article>
        {% endfor %}

        {{ ux_pagination(products) }}
    </turbo-frame>

The target route must return a response that contains the same frame
identifier. The rendered navigation uses native links, so an existing Turbo
Frame intercepts them without any Turbo-specific code in the bundle.

Bootstrap, Tailwind, and design systems
---------------------------------------

Use ``@UXPagination/theme/bootstrap.html.twig`` or
``@UXPagination/theme/tailwind.html.twig`` for the bundled markup. For a
component library or an application design system, pass an application Twig
template as the theme. Compose its markup from ordinary inheritance,
includes, or partials, as described in :doc:`rendering`.

Keep business logic out of the theme: URL composition and navigation state
already live on the pagination result.

Web APIs
--------

Both result types implement ``JsonSerializable``::

    // src/Controller/Api/ProductController.php
    namespace App\Controller\Api;

    use App\Repository\ProductRepository;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\UX\Pagination\PaginatorInterface;

    final class ProductController extends AbstractController
    {
        #[Route('/api/products', methods: ['GET'])]
        public function __invoke(
            ProductRepository $repository,
            PaginatorInterface $paginator,
        ): JsonResponse {
            return $this->json(
                $paginator->paginate($repository->createQueryBuilder('product')),
            );
        }
    }

The pagination result defines the response envelope. The application still
owns item normalization: expose arrays or DTOs, or configure Symfony
Serializer for the entity objects returned by the data source.

Serialization materializes the current slice. Standard numbered pagination
also resolves the exact total for its metadata and links, which runs the
adapter count unless the total was supplied or already cached. Lookahead
materializes its extra-row slice without a count query, and cursor pagination
materializes its cursor slice without a count query.

Both result types expose ``getLinks()`` for JSON:API, HAL, or
application-owned response builders. Cursor links preserve the route, the
filters, and the configured cursor parameter, so clients never decode or
reconstruct a token.

The numbered result serializes as:

.. code-block:: json

    {
        "items": [],
        "current_page": 2,
        "per_page": 20,
        "has_next": true,
        "has_previous": true,
        "total_items": 72,
        "total_pages": 4,
        "links": {
            "first": "/api/products",
            "last": "/api/products?page=4",
            "prev": "/api/products",
            "next": "/api/products?page=3"
        }
    }

The cursor result exposes its opaque tokens instead of assuming an API
format:

.. code-block:: json

    {
        "items": [],
        "per_page": 20,
        "cursor": null,
        "next_cursor": "opaque-signed-token",
        "previous_cursor": null,
        "has_next": true,
        "has_previous": false,
        "links": {
            "prev": null,
            "next": "/api/events?cursor=opaque-signed-token"
        }
    }

For an application-specific envelope, compose the same state without
reimplementing URL generation::

    // src/Controller/Api/EventController.php
    return $this->json([
        'items' => $events->getItems(),
        'pagination' => [
            'cursor' => $events->getCursor(),
            'next_cursor' => $events->getNextCursor(),
            'previous_cursor' => $events->getPreviousCursor(),
            'has_next' => $events->hasNext(),
            'has_previous' => $events->hasPrevious(),
        ],
        'links' => $events->getLinks(),
    ]);

This keeps JSON:API, HAL, and application-specific envelope decisions outside
the bundle while reusing its validated navigation state.

External APIs and search engines
--------------------------------

Offset callbacks
~~~~~~~~~~~~~~~~

Use ``fromCallbacks()`` when the source exposes offset and count callbacks::

    // src/Controller/SearchController.php
    $results = $paginator
        ->fromCallbacks(
            slice: fn (int $offset, int $limit): array => $search->find($query, $offset, $limit),
            count: fn (): int => $search->count($query),
        )
        ->perPage(20)
        ->paginate();

Both arguments accept any PHP callable, including invokable services injected
through the Symfony container. For reusable, application-wide support,
implement an adapter as described in :doc:`custom-adapters`.

GitHub GraphQL cursor connection
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

GitHub's GraphQL API is a useful example of a remote cursor source. A
connection requires either ``first`` or ``last``, between 1 and 100. Its
``pageInfo`` object returns the boundaries and the navigation state:

.. code-block:: text

    # src/Api/GitHub/PullRequests.graphql
    query PullRequests(
        $owner: String!,
        $name: String!,
        $first: Int,
        $last: Int,
        $after: String,
        $before: String
    ) {
        repository(owner: $owner, name: $name) {
            pullRequests(
                first: $first,
                last: $last,
                after: $after,
                before: $before
                orderBy: { field: CREATED_AT, direction: DESC }
            ) {
                nodes {
                    number
                    title
                }
                pageInfo {
                    startCursor
                    endCursor
                    hasPreviousPage
                    hasNextPage
                }
            }
        }
    }

The following code demonstrates only the pagination mapping. Keep the GitHub
client, authentication, transport errors, retries, and
``GitHubPullRequestConnection`` implementation in the application. Implement
``CursorAdapterInterface`` for that application source and map the cursor
contract directly:

================================= ================================================
UX Pagination state               GitHub GraphQL variables or response
================================= ================================================
First request                     ``first: limit``, ``after: null``
Forward ``CursorBoundary``        ``first: limit``, ``after: values[0]``
Backward ``CursorBoundary``       ``last: limit``, ``before: values[0]``
Next boundary                     ``endCursor`` when ``hasNextPage`` is true
Previous boundary                 ``startCursor`` when ``hasPreviousPage`` is true
================================= ================================================

The adapter can be cursor-only: it implements neither an exact count nor an
offset slice. It owns the GitHub connection order, so ``resolveCursorOrder()``
returns an opaque identity and application code does not call ``orderBy()``::

    // src/Pagination/GitHubPullRequestAdapter.php
    namespace App\Pagination;

    use App\Api\GitHub\GitHubPullRequestConnection;
    use Symfony\UX\Pagination\Adapter\CursorAdapterInterface;
    use Symfony\UX\Pagination\Cursor\CursorBoundary;
    use Symfony\UX\Pagination\Cursor\CursorOrder;
    use Symfony\UX\Pagination\Cursor\CursorSlice;
    use Symfony\UX\Pagination\Exception\InvalidArgumentException;

    final class GitHubPullRequestAdapter implements CursorAdapterInterface
    {
        public function supports(mixed $source): bool
        {
            return $source instanceof GitHubPullRequestConnection;
        }

        public function resolveCursorOrder(
            mixed $source,
            ?array $fields,
            ?string $direction,
        ): CursorOrder {
            \assert($source instanceof GitHubPullRequestConnection);

            if (null !== $fields || null !== $direction) {
                throw new InvalidArgumentException(
                    'GitHub owns this connection order; omit orderBy().',
                );
            }

            return CursorOrder::byIdentity(
                'github:pullRequests:CREATED_AT:DESC:v1',
            );
        }

        public function getCursorContext(
            mixed $source,
            ?string $context,
        ): string {
            \assert($source instanceof GitHubPullRequestConnection);

            return json_encode([
                'endpoint' => 'https://api.github.com/graphql',
                'owner' => $source->getOwner(),
                'repository' => $source->getRepository(),
                'connection' => 'pullRequests',
                'context' => $context,
            ], \JSON_THROW_ON_ERROR);
        }

        public function sliceWithCursor(
            mixed $source,
            ?CursorBoundary $boundary,
            int $limit,
            CursorOrder $order,
        ): CursorSlice {
            \assert($source instanceof GitHubPullRequestConnection);
            if ($limit > 100) {
                throw new InvalidArgumentException(
                    'GitHub accepts at most 100 pull requests per page.',
                );
            }

            $cursor = $this->getBoundaryCursor($boundary);
            $forward = $boundary?->pointsForward() ?? true;
            $connection = $source->fetch(
                first: $forward ? $limit : null,
                last: $forward ? null : $limit,
                after: $forward ? $cursor : null,
                before: $forward ? null : $cursor,
            );

            return $this->toCursorSlice($connection);
        }

        private function getBoundaryCursor(
            ?CursorBoundary $boundary,
        ): ?string {
            if (null === $boundary) {
                return null;
            }

            $values = $boundary->getValues();
            if (1 !== \count($values)
                || !\is_string($values[0])
                || '' === $values[0]
            ) {
                throw new InvalidArgumentException(
                    'Expected one non-empty GitHub cursor.',
                );
            }

            return $values[0];
        }

        /**
         * @param array{
         *     nodes: list<array{number: int, title: string}>,
         *     pageInfo: array{
         *         startCursor: string|null,
         *         endCursor: string|null,
         *         hasPreviousPage: bool,
         *         hasNextPage: bool
         *     }
         * } $connection
         */
        private function toCursorSlice(array $connection): CursorSlice
        {
            $pageInfo = $connection['pageInfo'];
            if ($pageInfo['hasNextPage']
                && null === $pageInfo['endCursor']
            ) {
                throw new \UnexpectedValueException(
                    'GitHub omitted endCursor.',
                );
            }
            if ($pageInfo['hasPreviousPage']
                && null === $pageInfo['startCursor']
            ) {
                throw new \UnexpectedValueException(
                    'GitHub omitted startCursor.',
                );
            }

            return new CursorSlice(
                items: $connection['nodes'],
                next: $pageInfo['hasNextPage']
                    ? new CursorBoundary([$pageInfo['endCursor']])
                    : null,
                previous: $pageInfo['hasPreviousPage']
                    ? new CursorBoundary(
                        [$pageInfo['startCursor']],
                        forward: false,
                    )
                    : null,
                hasNext: $pageInfo['hasNextPage'],
            );
        }
    }

Use the source through the normal cursor builder::

    // src/Controller/PullRequestController.php
    $pullRequests = $paginator
        ->cursor(new GitHubPullRequestConnection($owner, $repository))
        ->perPage(50)
        ->paginate();

The GitHub cursor becomes one value inside the signed UX Pagination token.
The token also binds the adapter's stable order identity and the source
context. Add stable filters to ``getCursorContext()`` when the connection
exposes them. Change the order identity when the remote sorting policy
changes.

Tokens are authenticated, not encrypted: neither value may contain
credentials or confidential data.

See `GitHub's cursor pagination guide`_ and the :doc:`custom-adapters`
contract for the complete adapter responsibilities.

Multiple lists on one page
--------------------------

Give each list a distinct page parameter::

    // src/Controller/DashboardController.php
    $orders = $paginator->query($orderQuery)->pageParameter('orders_page')->paginate();
    $alerts = $paginator->query($alertQuery)->pageParameter('alerts_page')->paginate();

Each paginator preserves the other list's page parameter. Exclude it when a
link should reset the other list::

    $orders = $paginator
        ->query($orderQuery)
        ->pageParameter('orders_page')
        ->excludeQueryParameters('alerts_page')
        ->paginate();

Integration checklist
---------------------

* Preserve real ``href`` values when Turbo, LiveComponent, or application
  JavaScript intercepts the links.
* Keep filters, tenant scope, and sorting identical across slice and count
  calls.
* In custom templates, preserve the navigation landmark, ``aria-current``,
  ``rel=prev``, and ``rel=next``.
* For external services, translate transport failures explicitly; never
  return an empty page for a failed request.
* Add one functional test at the integration boundary and keep strategy tests
  independent, as shown in :doc:`testing`.

.. _`GitHub's cursor pagination guide`: https://docs.github.com/en/graphql/guides/using-pagination-in-the-graphql-api
