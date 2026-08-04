Custom data-source adapters
===========================

An adapter connects the paginator to a data source. Write one when the
built-in array, Doctrine ORM and Doctrine DBAL adapters do not cover
your source.

Implement only the capabilities the source actually guarantees. Every
capability interface extends ``PaginationAdapterInterface``, which
defines ``supports()`` and carries the ``ux_pagination.adapter``
autoconfiguration tag.

For an offset source with an exact total, implement
``OffsetAdapterInterface``::

    // src/Pagination/SearchQueryAdapter.php
    namespace App\Pagination;

    use App\Search\SearchQuery;
    use Symfony\UX\Pagination\Adapter\OffsetAdapterInterface;

    final class SearchQueryAdapter implements OffsetAdapterInterface
    {
        public function supports(mixed $source): bool
        {
            return $source instanceof SearchQuery;
        }

        public function slice(mixed $source, int $offset, int $limit): array
        {
            \assert($source instanceof SearchQuery);

            return $source->fetch(offset: $offset, limit: $limit);
        }

        public function count(mixed $source): int
        {
            \assert($source instanceof SearchQuery);

            return $source->count();
        }
    }

With the usual service autoconfiguration, no manual tag is required.

Capabilities
------------

=============================== ================================================
Interface                       Source capability
=============================== ================================================
``PaginationAdapterInterface``  Source recognition through ``supports()``
``OffsetAdapterInterface``      Offset slice and exact count
``LookaheadAdapterInterface``   Offset slice with N+1 ``hasMore`` detection
``CursorAdapterInterface``      Opaque forward/backward cursor traversal
=============================== ================================================

The interfaces are independent. A remote GraphQL connection may implement
only ``CursorAdapterInterface``; it does not need dummy ``count()`` or
offset-based ``slice()`` methods. The built-in array, Doctrine ORM and
Doctrine DBAL adapters implement all three strategies.

A missing capability never changes the strategy silently. Requesting one
that the adapter does not implement produces an immediate,
adapter-specific exception.

Lookahead
~~~~~~~~~

Implement ``LookaheadAdapterInterface`` to support ``lookahead()``. Its
``sliceWithLookahead()`` method returns ``[items, hasMore]`` and must
never return more than the requested limit.

Cursor
~~~~~~

Implement ``CursorAdapterInterface`` to support cursor pagination. It
receives a decoded ``CursorBoundary`` and returns a ``CursorSlice``
containing the items, the next boundary, the previous boundary and the
``hasNext`` state.

``resolveCursorOrder()`` returns the effective ``CursorOrder``. The
cursor codec binds it to signed tokens before a boundary is decoded or
created.

For a field-based source, validate the fields passed to ``orderBy()``
and append a unique tie-breaker when the source can derive one::

    public function resolveCursorOrder(
        mixed $source,
        ?array $fields,
        ?string $direction,
    ): CursorOrder {
        \assert($source instanceof SearchQuery);

        if (null === $fields || null === $direction) {
            throw new \InvalidArgumentException('Call orderBy() for this source.');
        }

        if (!\in_array('objectID', $fields, true)) {
            $fields[] = 'objectID';
        }

        return CursorOrder::byFields($fields, $direction);
    }

For a remote connection that owns its order, reject an
application-supplied field order and return a stable opaque identity.
Callers then omit ``orderBy()``::

    public function resolveCursorOrder(
        mixed $source,
        ?array $fields,
        ?string $direction,
    ): CursorOrder {
        \assert($source instanceof SearchQuery);

        if (null !== $fields || null !== $direction) {
            throw new \InvalidArgumentException('This API owns its cursor order.');
        }

        return CursorOrder::byIdentity('search:created_at_desc:v1');
    }

Changing the opaque identity invalidates existing tokens. Include the
remote sort mode or a version in it, but never credentials.

The adapter also implements ``getCursorContext()``. Return a
deterministic description of everything that defines the logical source
query but is not already part of the order. The cursor codec binds this
value to every signed token.

The second argument is the optional value supplied by the application
through ``cursor()->context()``. Include it when it scopes the source. A
custom adapter may require it, ignore it, or combine it with source data,
but the resulting context must be deterministic.

For a remote API, that context typically contains the endpoint, the
filters and the tenant or repository identity. It must not contain the
current page items::

    public function getCursorContext(mixed $source, ?string $context): string
    {
        \assert($source instanceof SearchQuery);

        return json_encode([
            'index' => $source->index,
            'filters' => $source->filters,
            'context' => $context,
        ], \JSON_THROW_ON_ERROR);
    }

Adapter design
--------------

* Make ``supports()`` precise. The highest-priority matching adapter
  wins.
* Do not mutate the caller's source object.
* Return stable list ordering.
* Keep count and slice filters identical.
* Return the same cursor context for every page of the same logical
  query.
* Return the complete deterministic order from ``resolveCursorOrder()``.
* Reject unsupported cursor fields and directions.
* Add contract tests for empty, first, middle and last pages in both
  directions.

Adapter priority
----------------

Adapters use the standard Symfony tagged-service priority rules. Return
a default priority from the adapter when it should normally run before
another adapter supporting the same source::

    public static function getDefaultPriority(): int
    {
        return 100;
    }

An explicit ``priority`` attribute on the ``ux_pagination.adapter``
service tag takes precedence. Higher values run first; equal priorities
keep service registration order. Most adapters should keep the implicit
priority ``0`` and use a narrow ``supports()`` implementation.

For a one-off offset integration that can provide both a slice callback
and an exact count callback, prefer ``fromCallbacks()`` from
:doc:`integrations`. The returned builder also supports ``lookahead()``,
but ``fromCallbacks()`` does not support cursor pagination.
