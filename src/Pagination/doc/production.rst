Production, security and performance
====================================

Security model
--------------

Page numbers and filters are untrusted Request input. The bundle
validates page and cursor transport values. Authorization and query
scoping remain application responsibilities::

    // src/Repository/InvoiceRepository.php
    $query = $this->createQueryBuilder('invoice')
        ->andWhere('invoice.tenant = :tenant')
        ->setParameter('tenant', $tenant);

Never build Doctrine field names from a user-provided sort parameter.
The ORM adapter validates cursor fields against mapping metadata. The
DBAL adapter validates identifier syntax only, and custom adapters define
their own checks. Use a fixed allow-list at the controller boundary for
every strategy.

Cursor tokens are signed, not encrypted. They are limited to 4096 bytes
and 16 ordered scalar values. Boundary values remain readable after
decoding the token. The source context is hashed, but low-entropy values
can still be guessed. Treat tokens as opaque and do not rely on them to
conceal sensitive data.

Secret rotation, effective-order changes and source-context changes
invalidate existing cursors. The built-in Doctrine adapters include the
query and its parameters in that context. A custom adapter controls
invalidation through ``getCursorContext()``.

Cursor traversal is stable against offset shifts, not against changes to
the ordered values themselves. Use immutable ordering values or database
snapshot semantics when a traversal must represent one fixed point in
time. Add an explicit cursor ``context()`` for tenant, user, shard or
filter boundaries not captured by the query parameters.

Database performance
--------------------

* Index filter and order columns together.
* Use lookahead when totals are not useful.
* Use cursor pagination instead of raising ``max_offset`` for unbounded
  feeds.
* Inspect both data and count queries with production-like cardinality.
* Provide ``total()`` for expensive aggregate queries.
* Keep configured page sizes within measured application limits.
* Keep array cursor sources bounded: they are materialized and sorted
  in PHP.

Offset pagination rejects offsets above 100,000 by default, before
querying the adapter. The built-in database cursor adapters fetch at most
``perPage + 1`` rows. A custom adapter controls its own upstream request.

HTTP and frontend behavior
--------------------------

All controls are ordinary links and remain cacheable according to
application rules. Include filter, sort and pagination parameters in
reverse-proxy cache keys. Do not cache tenant-scoped content across
authorization boundaries.

UX Pagination does not prefetch links. When the application or Turbo
enables prefetching, it owns the cache, authorization, request-cost and
reduced-data policy. Pagination links remain ordinary URLs when no
frontend integration is present.

Operational checklist
---------------------

* Keep the effective cursor secret stable across every application
  instance.
* Deploy the same bundle version and cursor order everywhere.
* Monitor invalid page/cursor rates and deep-offset rejections.
* Trace data-query and count-query latency separately.
* Test forward and backward cursors after schema or query changes.
* Verify the no-JavaScript journey and translated labels.
* Run ``EXPLAIN`` for cursor boundaries, not only the first page.
