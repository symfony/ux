Symfony UX Disclose
===================

Symfony UX Disclose renders protected or sensitive values behind a masked
trigger. The real value is *never* part of the initial HTML: it only reaches the
browser after an explicit click, through a server endpoint that enforces
authorization, a rate limit and an audit trail before returning anything. It is
part of `the Symfony UX initiative`_.

.. versionadded:: 3.5

Why should I use it?
--------------------

Personally identifiable data (emails, phone numbers, addresses, identifiers)
shown to every authorized user is an *exfiltration vector*: any user with
legitimate access, acting with bad intentions, can harvest thousands of records
in seconds by opening pages or scraping the HTML, without any of it being
noticed.

Disclose changes the economics of mass disclosure:

- the value is not in the HTML, so a page load or a scraped dump leaks nothing;
- revealing a value takes one explicit click, which is hard to automate silently;
- every click is rate-limited per user (falling back to the IP), so a bulk
  harvest hits the quota immediately;
- every attempt is audited, so abuse is loud and traceable.

Installation
------------

.. caution::

    Before you start, make sure you have `StimulusBundle configured in your app`_.

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-disclose

If you're using WebpackEncore, install your assets and restart Encore (not
needed if you're using AssetMapper):

.. code-block:: terminal

    $ npm install --force
    $ npm run watch

.. note::

    For more complex installation scenarios, you can install the JavaScript
    assets through the `@symfony/ux-disclose npm package`_.

Basic usage
-----------

.. code-block:: twig

    <twig:ux:disclose :context="client" field="email" />

The component renders two views: the **reveal button** (a masked placeholder)
and the **data display**, hidden until the value is disclosed. Both views are
custom Twig blocks, so frontend developers shape them as they want:

.. code-block:: twig

    <twig:ux:disclose :context="client" field="email">
        {% block button %}
            <button data-disclose-target="button" data-action="disclose#reveal">
                Show the email
            </button>
        {% endblock %}

        {% block content %}
            <div data-disclose-target="content" hidden>
                <strong data-disclose-target="value"></strong>
                <button data-disclose-target="hideButton" data-action="disclose#hide" hidden>
                    Hide
                </button>
            </div>
        {% endblock %}
    </twig:ux:disclose>

The `context` is a ``DiscloseContext`` or any object a subject resolver can
resolve (an entity, an ODM document, or any value handled by a custom
resolver). The `field` picks which property is disclosed.

Revealing a whole block
-----------------------

A disclosure can return a whole server-rendered block instead of a single
value, for example a set of fields inside a table. Author a ``reveal`` block
inside the component tag; on click, the bundle renders it server-side with the
resolved subject (and any ``vars`` you pass) and the controller injects the
resulting HTML:

.. code-block:: twig

    <twig:ux:disclose :context="client" :vars="{ title: 'Profile' }" toggle>
        {% block reveal %}
            <table class="table">
                <tr><th>Email</th><td>{{ subject.email }}</td></tr>
                <tr><th>Phone</th><td>{{ subject.phone }}</td></tr>
            </table>
        {% endblock %}
    </twig:ux:disclose>

The ``reveal`` block is compiled into the component tag body but is never
rendered on the initial mount: the secret stays out of the page HTML. The
disclosure endpoint reloads that block at click time and renders it with
``subject`` (the resolved object), ``context`` (the signed context) and the
``vars`` you passed.

The block receives ``subject`` and ``context`` exactly as if it were a native
Twig block, so it can use any Twig feature (conditionals, includes, macros):

.. code-block:: twig

    {% block reveal %}
        <tr>
            <td>{{ subject.email }}</td>
            <td>{{ subject.phone }}</td>
        </tr>
    {% endblock %}

The returned HTML is injected through ``innerHTML``: the revealed markup is
always produced by your own server-side templates, signed by the bundle.

Subject resolution
------------------

The bundle owns the endpoint. A ``SubjectResolverInterface`` is triggered to
turn the signed context into the subject object, whatever the data source:

.. code-block:: php

    use Symfony\UX\Disclose\Context\DiscloseContext;
    use Symfony\UX\Disclose\Subject\SubjectResolverInterface;

    final class ApiSubjectResolver implements SubjectResolverInterface
    {
        public function supports(DiscloseContext $context): bool
        {
            return str_starts_with($context->class, 'Api\\');
        }

        public function resolve(DiscloseContext $context): object
        {
            // fetch the subject from your data source
        }
    }

The resolvers run in order, the first supporting resolver wins. Doctrine ORM and
MongoDB ODM ship as optional built-in resolvers and register automatically when
the corresponding package is installed. When no resolver supports the class, the
endpoint answers with a configuration error; when the subject cannot be found,
it answers ``404``.

The discloser
-------------

A ``DiscloserInterface`` implementation states the authorization policy and
extracts the value:

.. code-block:: php

    use Symfony\Bundle\SecurityBundle\Security;
    use Symfony\UX\Disclose\Context\DiscloseContext;
    use Symfony\UX\Disclose\DiscloserInterface;

    final class ClientDiscloser implements DiscloserInterface
    {
        public function supports(object $subject): bool
        {
            return $subject instanceof Client;
        }

        public function isGranted(Security $security, object $subject, DiscloseContext $context): bool
        {
            // Delegate to the Security component (voters, roles). The context
            // carries the field, so the policy can be field aware.
            return $security->isGranted('CLIENT_'.$context->field, $subject);
        }

        public function disclose(object $subject, DiscloseContext $context): string
        {
            /** @var Client $subject */
            return $subject->{$context->field};
        }
    }

Authorization
-------------

The bundle enforces ``DiscloserInterface::isGranted()`` before anything else.
It passes the Symfony ``Security`` helper, the resolved subject and the
disclose context, so the policy delegates to the Security component (voters,
roles). Unauthorized requests receive an HTTP ``403`` and the value is never
fetched. When ``SecurityBundle`` is not installed, every disclosure is denied.
Classify the disclosed fields server-side; never rely on the client hiding them.

Rate limiting
-------------

The bundle proposes a rate limiter by default (``fixed_window``, 10 disclosures
every 10 minutes, per user and falling back to per IP). Override it exactly like
any framework rate limiter:

.. code-block:: yaml

    # config/packages/rate_limiter.yaml
    framework:
        rate_limiter:
            ux_disclose:
                policy: fixed_window
                limit: 20
                interval: '1 hour'

Set ``policy: no_limit`` to disable the limit. Use a persistent cache pool in
production (a DBAL-backed pool, for instance) so a server restart never resets
the quotas:

.. code-block:: yaml

    framework:
        cache:
            pools:
                disclose.rate_limiter:
                    adapter: cache.adapter.doctrine_dbal
                    provider: 'doctrine.dbal.default_connection'
                    default_lifetime: 0
        rate_limiter:
            ux_disclose:
                cache_pool: disclose.rate_limiter

Combine several limiters
~~~~~~~~~~~~~~~~~~~~~~~~

A disclosure can be gated by several framework limiters at once, for example
a short burst window (human speed) and a daily quota (total volume). List the
limiter names in the ``rate_limiter`` option, as a single string or an array.
The bundle combines them with the RateLimiter ``CompoundLimiter``: a request is
only accepted when every listed limiter accepts it. The limiters are consumed in
turn without rollback, so list the most restrictive one first: if a later limiter
rejects the request, the earlier ones have already spent a token.

.. code-block:: yaml

    framework:
        rate_limiter:
            ux_disclose_burst:
                policy: fixed_window
                limit: 5
                interval: '1 minute'
            ux_disclose_daily:
                policy: fixed_window
                limit: 200
                interval: '1 day'

    disclose:
        rate_limiter: ['ux_disclose_burst', 'ux_disclose_daily']

Concurrent requests and the lock
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Rate limiter ``consume()`` is a read-modify-write on the cache. Without a lock,
a burst of simultaneous requests can race past the quota (each of them reads the
same remaining budget). The Lock component is a **hard requirement** of the
bundle: it registers a flock lock factory in the cache directory and proposes it
as the default ``lock_factory`` of the rate limiter, so concurrent requests
serialize and exactly the configured quota passes. To plug another lock store
(for example a DBAL-backed one), set ``lock_factory`` to your own factory:

To key the limiter differently than user-then-IP, set ``rate_limiter_subject_factory``
to a service implementing ``DiscloseRateLimitSubjectFactoryInterface``.

Audit
-----

Every disclosure attempt is logged with the identity, the target class and
identifier, and the outcome (``attempt``, ``success``, ``auth_denied``,
``rate_limited``). Point the ``logger`` option at a dedicated logger and tail it
to detect bulk harvesting:

.. code-block:: yaml

    disclose:
        logger: monolog.logger.audit

Applications can also subscribe to the ``disclose.attempt``, ``disclose.success``
and ``disclose.rejected`` events for a custom audit store.

Security considerations
-----------------------

- The value never enters the initial HTML: the page contains only the signed
  reference. Source view, screenshots and DOM scraping leak nothing.
- By default the fetched value is inserted with ``textContent``, never as HTML.
  Only the ``reveal`` block injects server-side HTML, and it is your own
  template rendered by the bundle.
- The signed reference cannot be tampered with to target other records.
- Every reveal is a deliberate click, rate-limited, and audited: exfiltration is
  slow, loud and traceable.

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as the
Symfony framework: https://symfony.com/doc/current/contributing/code/bc.html.

.. _`the Symfony UX initiative`: https://ux.symfony.com/
.. _StimulusBundle configured in your app: https://symfony.com/bundles/StimulusBundle/current/index.html
.. _`@symfony/ux-disclose npm package`: https://www.npmjs.com/package/@symfony/ux-disclose
