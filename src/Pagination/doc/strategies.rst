Choose a pagination strategy
============================

The three strategies return iterable objects implementing
``PaginationInterface``. Templates that only iterate items and render
previous/next links can switch strategy without changing their structure.

Start with the product question
-------------------------------

Do not choose from method names alone. Ask what the screen promises:

1. **Must users see an exact total or numbered page links?**
   Start with offset.
2. **Is previous/next enough, and is the count expensive or meaningless?**
   Use lookahead.
3. **Can rows be inserted or deleted while a user traverses a large result?**
   Use a cursor with a stable unique order.

Then check the operational cost:

* Offset and lookahead become slower at deep pages because the data
  source must skip rows.
* Lookahead saves the count query, not the offset.
* Cursor navigation keeps boundary queries stable but gives up totals
  and random page access.

Offset
------

Use offset pagination for administration lists, catalogs and result sets
where page numbers and an exact total matter.

::

    // src/Controller/ProductController.php
    $products = $paginator
        ->query($repository->createQueryBuilder('product'))
        ->perPage(20)
        ->sliding(size: 5)
        ->paginate();

``size`` is the maximum number of consecutive pages in the moving
window, including the current page. First/last-page shortcuts and
ellipses are added outside that window when needed.

Offset pagination fetches one page with ``LIMIT`` and ``OFFSET``.
Reading totals or numbered links also runs a count query. Deep offsets
are rejected above the configured ``max_offset``.

Lookahead
---------

Use lookahead for feeds and searches that need a reliable "next" link
but not an exact total.

::

    // src/Controller/ActivityController.php
    $query = $repository->createQueryBuilder('activity')
        ->orderBy('activity.id', 'DESC');

    $activity = $paginator
        ->query($query)
        ->perPage(30)
        ->lookahead()
        ->paginate();

The adapter requests ``perPage + 1`` rows. The extra row reveals whether
a next page exists and is removed from the returned items.
``getTotalItems()`` and ``getTotalPages()`` return ``null``.

Lookahead still resolves a one-based page number. A direct request such as
``/activity?page=17`` works within ``max_offset``, but the bundled themes show
only previous and next links because the final page is unknown.

There is intentionally no weaker "skip count" mode. Without the extra
row, a full final page cannot be distinguished from a page followed by
more results, so the generated next link could lead to an empty page.

Cursor
------

Use cursor pagination for large or frequently changing datasets with a
stable total order.

::

    // src/Controller/AuditLogController.php
    $entries = $paginator
        ->cursor($repository->createQueryBuilder('entry'))
        ->orderBy(['createdAt', 'id'], 'DESC')
        ->perPage(50)
        ->paginate();

There is no count, no page number and no random access. Forward and
backward links carry signed opaque tokens. See :doc:`cursor` for the
ordering contract.

Decision table
--------------

=============================== =========== =============== ===================
Requirement                     Offset      Lookahead       Cursor
=============================== =========== =============== ===================
Exact total                     Yes         No              No
Numbered links                  Yes         No              No
Direct page-number access       Yes         Yes             No
Count query                     When needed No              No
Deep-page database cost         Grows       Grows           Stable with
                                                            matching index
Resists insert/delete drift     No          No              Yes
Stable unique order required    Recommended Same as offset  Required
=============================== =========== =============== ===================

Production checklist
--------------------

Before shipping the selected strategy:

* confirm whether the UI actually displays the exact total;
* inspect both the slice and count query with production-like data;
* keep filters and authorization scopes identical between slice and
  count;
* add a unique tie-breaker to every cursor order;
* test first, middle and final navigation in both directions;
* keep a working no-JavaScript path.
