Doctrine ORM and DBAL
=====================

Pass a Doctrine ORM ``QueryBuilder`` directly to the paginator. The
adapter clones it before applying limits, offsets, counts or cursor
predicates. The application's original builder is never mutated.

Filtered lists
--------------

Build the filtered query in the repository::

    // src/Repository/OrderRepository.php
    public function queryForStatus(?string $status): QueryBuilder
    {
        $query = $this->createQueryBuilder('orders')
            ->orderBy('orders.createdAt', 'DESC');

        if (null !== $status) {
            $query
                ->andWhere('orders.status = :status')
                ->setParameter('status', $status);
        }

        return $query;
    }

Then paginate it in the controller::

    // src/Controller/OrderController.php
    $orders = $paginator->paginate($repository->queryForStatus($status));

This numbered-pagination example keeps the repository ``ORDER BY``. For
cursor pagination, leave the source query unordered and configure the
order with ``cursor()->orderBy()`` instead.

Counts and joins
----------------

The adapter uses a plain query when joins cannot duplicate root rows. For
collection-valued or unknown joins, it uses Doctrine's paginator and
counts distinct root entities.

The bundle deliberately rejects inferred counts for ``GROUP BY``,
``HAVING`` and multiple root aliases. Provide an explicit total::

    // src/Controller/ReportController.php
    $rows = $paginator
        ->query($reportQuery)
        ->total(fn (): int => $reportRepository->countRows($filters))
        ->paginate();

The callable is lazy and evaluated at most once. It may also be an
invokable service injected into the controller. If the UI does not need
a total, use ``lookahead()`` instead.

Cursor indexes
--------------

Match the index to the equality filters and the cursor order. For a
tenant feed ordered by creation date and identifier:

.. code-block:: sql

    -- migrations/Version20260101000000.php (equivalent SQL)
    CREATE INDEX event_tenant_cursor_idx
        ON event (tenant_id, created_at DESC, id DESC);

The matching query filters on the tenant and orders on the indexed
columns::

    // src/Controller/EventController.php
    $events = $paginator
        ->cursor($repository->queryForTenant($tenant))
        ->orderBy(['createdAt', 'id'], 'DESC')
        ->context('tenant:'.$tenant->getId())
        ->paginate();

Use ``EXPLAIN`` on production-like data. An index that works for the
first page should also serve the cursor boundary predicate.

Cursor restrictions
-------------------

Cursor fields must be non-nullable mapped scalar fields on the root
entity. Associations, unmapped aliases, nullable fields and invalid field
names are rejected against ORM metadata. Supported Doctrine types include
small, regular and big integers, string, GUID, float, decimal, boolean,
``datetime`` and ``datetimetz`` fields.

The adapter appends every missing entity identifier field to make the
effective order deterministic. The direction passed to ``orderBy()``
applies to every requested and appended field. Mixed directions are not
supported.

Metadata validation prevents DQL injection, but it is not an application
sorting policy. Map request sort values to a fixed allow-list before
passing field names to ``orderBy()``.

The source query must contain one root entity and no existing
``ORDER BY``. Configure its complete order through
``cursor()->orderBy()``.

Doctrine DBAL
-------------

Pass a DBAL ``QueryBuilder`` through the same API when the application
needs SQL-level control or associative rows without ORM hydration::

    // src/Controller/AuditLogController.php
    $query = $connection->createQueryBuilder()
        ->select('entry.id', 'entry.created_at', 'entry.message')
        ->from('audit_entry', 'entry')
        ->andWhere('entry.tenant_id = :tenant')
        ->setParameter('tenant', $tenant->getId());

    $entries = $paginator
        ->cursor($query)
        ->orderBy(['entry.created_at', 'entry.id'], 'DESC')
        ->perPage(50)
        ->paginate();

The DBAL adapter supports numbered, lookahead and cursor pagination. It
clones the source before adding limits, count wrapping, boundary
predicates or order, and returns rows from ``fetchAllAssociative()``.

Cursor tokens are automatically bound to the DBAL SQL, the parameter
types and the normalized parameter values. Add ``context()`` only when
the same query must be isolated further by an application boundary that
its parameters do not represent.

Unlike ORM, DBAL has no mapping metadata and does not append a primary
key. It validates column-name syntax, not whether a column exists or may
be exposed as a sort mode. Keep the complete order in application code
and map request values through an allow-list.

For numbered pagination, the exact count wraps the filtered source query
in a subquery and removes its order and limits. For cursor pagination:

* pass column names or qualified column names, never request input;
* select every cursor column under its unqualified name;
* leave the source without ``ORDER BY``: cursor pagination owns it;
* use only non-null cursor values;
* include a unique final column such as the primary key;
* use one direction for every cursor column;
* add an index matching equality filters followed by the cursor columns.

The last point is essential for deep-page performance. Cursor pagination
avoids ``OFFSET``, but the database still needs a matching index to seek
efficiently from the boundary.

DBAL 4.4 or later is required. Version 4.4 introduced the public
``QueryBuilder::sub()`` API used to wrap exact-count queries. The
adapter is registered only when DBAL is installed.
