Cursor pagination
=================

Cursor pagination traverses a result set forward and backward without
``OFFSET`` or a count query. The builder reads ``?cursor=...``
automatically and returns a ``CursorPaginationInterface`` result.

Doctrine quick start
--------------------

::

    // src/Controller/EventController.php
    $events = $paginator
        ->cursor($repository->createQueryBuilder('event'))
        ->orderBy(['occurredAt', 'id'], 'DESC')
        ->perPage(25)
        ->paginate();

.. code-block:: html+twig

    {# templates/event/index.html.twig #}
    {% for event in events %}
        <article>{{ event.occurredAt|date('c') }} - {{ event.label }}</article>
    {% endfor %}

    {{ ux_pagination(events) }}

For Doctrine ORM, the entity identifier is appended automatically when it
is not already present. Including it explicitly documents the effective
order and makes the required database index obvious. Doctrine DBAL has no
entity metadata, so its complete stable order must be explicit.

``orderBy()`` is required for the built-in array, Doctrine ORM and
Doctrine DBAL adapters: UX Pagination owns their effective order. It is
optional for a custom remote adapter that owns an opaque connection
order; that adapter returns a stable ``CursorOrder::byIdentity()``
instead. See :doc:`custom-adapters` and the worked GitHub GraphQL
pagination mapping in :doc:`integrations`.

Stable ordering
---------------

The ordered fields must form a total deterministic order. A timestamp
alone is usually insufficient because several rows can share it. Add a
unique tie-breaker::

    // Correct: equal timestamps are ordered by id.
    ->orderBy(['createdAt', 'id'], 'DESC')

The direction passed to ``orderBy()`` applies to every field. Mixed
directions such as ``createdAt DESC, id ASC`` are not supported.

Cursor navigation resists the offset shifts caused by inserts and
deletions, as long as the ordered values of already-visible rows remain
stable. It is not a database snapshot. Changing an ordered value, or
inserting a row behind the current boundary, can move that row outside
the remaining traversal.

Use snapshot isolation or an immutable feed key when the product requires
a fixed point-in-time view.

PHP cursor values implementing ``DateTimeInterface`` are normalized to
UTC before they are compared or encoded. Equivalent instants keep the
same order even when the objects use different timezone offsets. Scalar
DBAL values are compared as returned by the driver, so use a canonical
database representation for ordered date fields.

Cursor pagination owns Doctrine's ``ORDER BY``. Do not order the source
``QueryBuilder`` first::

    // src/Repository/EventRepository.php
    $query = $this->createQueryBuilder('event'); // no orderBy()

    $events = $paginator
        ->cursor($query)
        ->orderBy(['createdAt', 'id'], 'DESC')
        ->paginate();

Tokens and context
------------------

Tokens are versioned and signed with the configured cursor secret. Each
token is bound to:

* the effective order fingerprint;
* the stable source context returned by the selected adapter;
* the configured cursor secret.

The built-in adapters derive the source context differently:

* Doctrine ORM includes the root entity, DQL and normalized parameters;
* Doctrine DBAL includes the SQL, parameter types and normalized values;
* the array adapter requires an explicit application context;
* a custom adapter defines the context for its own logical source.

An adapter may also include the value passed to ``context()``. Use it for
application boundaries that are not already represented by the source.

The cursor secret is optional and falls back to ``kernel.secret``.
Configure a dedicated value only when cursor URLs need an independent
rotation policy:

.. code-block:: yaml

    # config/packages/ux_pagination.yaml
    ux_pagination:
        cursor:
            secret: '%env(UX_PAGINATION_CURSOR_SECRET)%'

The signing service is lazy: numbered pagination never reads or
initializes the cursor secret. If neither ``cursor.secret`` nor a
non-empty ``kernel.secret`` is available, the first cursor signing or
verification operation fails explicitly.

Bind a cursor to a tenant or another business boundary::

    // src/Controller/InvoiceController.php
    $invoices = $paginator
        ->cursor($repository->queryForTenant($tenant))
        ->orderBy(['issuedAt', 'id'], 'DESC')
        ->context('tenant:'.$tenant->getId())
        ->paginate();

Pass an explicit ``context()`` for implicit boundaries not represented
in the query and its parameters: active Doctrine filters, the current
shard, tenant state or a user-specific scope.

Tokens are authenticated, not encrypted. Their boundary values can be
decoded by clients. The source context is stored as a hash, not as plain
text, but low-entropy values can still be guessed. Treat tokens as opaque
transport values and do not rely on them to conceal sensitive data.

The built-in array adapter requires an explicit, stable context::

    $events = $paginator
        ->cursor($events)
        ->orderBy('id', 'ASC')
        ->perPage(25)
        ->context('event-feed')
        ->paginate();

Keep that context identical while the source grows or changes. It
identifies the application feed. Do not derive it from the current
items: every insertion or deletion would then invalidate existing
cursors.

A custom adapter decides whether callers must provide ``context()``. Its
``getCursorContext()`` method must always return the same value for the
same logical source, regardless of the current page.

Navigation and APIs
-------------------

The result exposes the items, the traversal state and generated URLs::

    $events->getItems();
    $events->hasNext();
    $events->hasPrevious();
    $events->getNextCursor();
    $events->getPreviousCursor();
    $events->getNextUrl();
    $events->getPreviousUrl();
    $events->getLinks(); // ['prev' => ?string, 'next' => ?string]

The JSON representation contains ``items``, ``per_page``, ``cursor``,
``next_cursor``, ``previous_cursor``, ``has_next``, ``has_previous`` and
the bundle-generated ``links.prev`` / ``links.next`` URLs. Clients
follow those URLs without decoding or reconstructing the opaque cursor.

Invalid cursors
---------------

Malformed, unsigned, context-invalidated or overlong request values
throw ``InvalidCursorException``. Let Symfony render the 400 response in
production. Do not silently restart on page one: that hides broken links
and can mislead API clients.

Changing the effective cursor secret, source context or effective order
invalidates existing tokens by design. With the built-in Doctrine
adapters, changing the query or its parameters changes the source context.
For a custom adapter, invalidation follows the value returned by
``getCursorContext()``.
