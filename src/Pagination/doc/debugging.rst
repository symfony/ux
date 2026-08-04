Debugging and troubleshooting
=============================

Inspect the service and configuration
-------------------------------------

Use the console to inspect the wiring:

.. code-block:: terminal

    $ php bin/console debug:autowiring PaginatorInterface
    $ php bin/console debug:config ux_pagination
    $ php bin/console debug:twig --filter=ux_pagination

Use the Symfony profiler to inspect the current query parameters, the
generated route, the Doctrine queries and the query count.

HTTP and failure policy
-----------------------

The paginator never redirects an invalid or out-of-range request. Symfony
converts exceptions implementing ``HttpExceptionInterface`` into their
HTTP status when they reach the kernel:

==================================== ====== ====================================
Condition                            Status Behavior
==================================== ====== ====================================
Malformed request page               400    HttpFoundation rejects conversion
Non-positive request page            400    One-based invariant is rejected
Malformed or invalidated cursor      400    ``InvalidCursorException``
Configured maximum offset exceeded   400    ``OffsetLimitExceededException``
Page beyond the final page           200    Empty out-of-range result by default
Page beyond the final page           404    With ``throwOnOutOfRange()``
Full navigation exceeds its limit    500    Developer configuration error
==================================== ====== ====================================

The 404 policy performs an exact count. It is opt-in because lookahead
and cursor pagination intentionally avoid totals. For a redirect, a
canonical URL or another response, implement that policy in the
controller or in an application event listener.

Malformed page
--------------

When ``paginate()`` receives no explicit page, it delegates HTTP
conversion to the current Request parameter bag. HttpFoundation rejects
malformed values; UX Pagination then rejects zero and negative integers.
Neither the paginator nor a package exception stores the raw input.

Leave the page argument empty in an ordinary controller to use this
automatic resolution. When the application passes
``paginate(page: $page)``, the value is already typed PHP input and
RequestStack is intentionally not consulted.

Unsafe offset
-------------

``OffsetLimitExceededException`` protects the source from unexpectedly
deep offsets. Check whether a bot or an unbounded UI produced the URL.
Raise ``max_offset`` only for a measured, indexed use case; otherwise
use cursor pagination.

Unsupported Doctrine count
--------------------------

``UnsupportedDoctrineQueryException`` reports ``GROUP BY``, ``HAVING``
or multiple roots whose total cannot be inferred safely. Use
``total()``, ``lookahead()`` or a custom adapter.

Invalid cursor
--------------

``InvalidCursorException`` can indicate a malformed token, a changed
application secret, context or order. With the built-in Doctrine adapters, a
changed source query or bound source parameter also changes the cursor
context. Compare the complete source query and the ``orderBy()`` call between
requests. For a custom adapter, inspect ``getCursorContext()`` and
``resolveCursorOrder()``. Do not log the full token at info level; a digest is
sufficient for correlation.

Cursor query already ordered
----------------------------

Doctrine cursor pagination rejects a source ``QueryBuilder`` with
``ORDER BY``. Remove it and express the entire deterministic order with
``cursor()->orderBy()``.

LiveComponent links perform a full navigation
---------------------------------------------

The links keep real ``href`` values, so a missing LiveComponent runtime
falls back to an ordinary request by design. When the fallback is not
intended, confirm that:

* LiveComponent is installed;
* the component root contains ``{{ attributes }}``;
* the component uses ``ComponentWithPaginationTrait``;
* the renderer receives ``link_attributes: this.paginationLinkAttributes``.

Theme template not found
------------------------

The theme is passed directly to Twig and must be a complete template
name. Use an application path such as ``pagination/product.html.twig``
or a namespace such as ``@UXPagination/theme/bootstrap.html.twig``.
Place bundle overrides under
``templates/bundles/UXPaginationBundle/theme/``.

For a new application theme, pass an explicit path and keep repeated
markup in application-owned includes or partials. The pagination result
supplies URLs and state, so these themes do not need to copy PHP logic.
