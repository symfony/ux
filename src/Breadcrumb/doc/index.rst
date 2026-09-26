Symfony UX Breadcrumb
=====================

.. caution::

    **EXPERIMENTAL** This bundle is currently experimental and is likely to
    change, possibly significantly, before its first stable release.

Symfony UX Breadcrumb declares the breadcrumb trail of a page on its controller,
with a repeatable ``#[Breadcrumb]`` attribute. The trail is collected unresolved
onto the request, and only turned into labels and URLs when a template asks for
it.

That laziness is the point. Crumb expressions routinely dereference Doctrine
associations, so resolving them on every request that merely *matched* such a
controller would trigger lazy-loading queries for nothing. A redirect, a Turbo
Stream, a JSON response or a 304 pays nothing at all.

Installation
------------

.. code-block:: terminal

    $ composer require symfony/ux-breadcrumb

Usage
-----

Declare the crumbs in trail order, top to bottom::

    // src/Controller/ProductViewController.php
    namespace App\Controller;

    use App\Entity\Product;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;

    #[Breadcrumb(label: 'product.index.breadcrumb', route: 'product_index')]
    #[Breadcrumb(label: 'product.view.breadcrumb', translationParameters: ['name' => 'product.name'])]
    final class ProductViewController extends AbstractController
    {
        #[Route('/products/{slug}', name: 'product_view')]
        public function __invoke(Product $product): Response
        {
            return $this->render('product/view.html.twig');
        }
    }

.. code-block:: twig

    {# templates/product/view.html.twig #}
    {{ ux_breadcrumb() }}

The attribute targets both classes and methods, so a classic multi-action
controller can declare the shared head of the trail on the class and the leaf on
each action. Class-level crumbs always come first.

The attribute
-------------

* ``label`` (``string``): the translation key, or the literal label when ``translationDomain`` is ``false``
* ``route`` (``?string``): name of the route to link to
* ``inheritedParameters`` (``array<int, string>``): a **list of names** taken from the matched route
* ``computedParameters`` (``array<string, string>``): a **map** of expressions, evaluated against the controller's arguments
* ``translationDomain`` (``string|false|null``): ``null`` for the default domain, a domain name, or ``false`` to skip translation
* ``translationParameters`` (``array<string, string>``): a **map** of expressions, fed to the translator
* ``extra`` (``array<string, mixed>``): arbitrary data forwarded untouched to the resolved item, never read by the bundle

The two URL parameter bags differ in where the value comes from, not in where it goes:

* ``inheritedParameters`` is a **list of names** taken from the already-matched route (``_route_params``). The values exist, so nothing is evaluated.
* ``computedParameters`` is a **map** whose values are ExpressionLanguage expressions evaluated against the controller's arguments.

``translationParameters`` is a map of expressions too, but it feeds the translator rather than the URL.

::

    #[Breadcrumb(
        label: 'product.view.breadcrumb',
        route: ProductRouteName::View->value,
        inheritedParameters: ['slug'],
        computedParameters: ['state' => 'product.state'],
        translationParameters: ['name' => 'product.name'],
    )]

A route name is a string, as everywhere else in Symfony.
If your application keeps its route names in a backed enum, pass the case's ``->value``, which is a valid constant expression in an attribute argument.

Neither bag decides whether a parameter lands in the path or in the query string.
The URL generator places each name in the path when the route declares a placeholder for it, and in the query string otherwise, so the example above generates ``/products?state=published`` while ``slug`` fills the ``{slug}`` placeholder.
An inherited name the route has no placeholder for lands in the query string just the same.
When both bags name the same parameter, the computed value wins.

Only the controller arguments a crumb expression actually names are kept on the trail, so the whole argument list, and notably the ``Request``, is not pinned into the request attributes until render time.

Resolution degrades rather than throwing.
A crumb pointing at an unknown route, at one whose required parameters are missing, or carrying an expression that cannot be evaluated against this action's arguments, resolves to ``url === null`` and renders as plain text.
A translation parameter that cannot be evaluated leaves its placeholder in the label rather than taking the page down.

Rendering
---------

``ux_breadcrumb_items()`` returns a list of items, each carrying ``label``, ``url``
and ``extra``. Rendering them yourself is the expected path:

.. code-block:: html+twig

    {% set items = ux_breadcrumb_items() %}

    {% if items is not empty %}
        <nav aria-label="Breadcrumb">
            <ol>
                {% for item in items %}
                    <li>
                        {% if not loop.last and item.url is not null %}
                            <a href="{{ item.url }}">{{ item.label }}</a>
                        {% else %}
                            <span aria-current="page">{{ item.label }}</span>
                        {% endif %}
                    </li>
                {% endfor %}
            </ol>
        </nav>
    {% endif %}

The last crumb is the current page, so it is never a link even when it carries a route.
A mid-trail crumb without a route renders as plain text rather than an empty ``href``, but it is not the current page, so only the last crumb carries ``aria-current="page"``.

``ux_breadcrumb()`` renders the bundled, deliberately unstyled theme. Extend it and
override its ``*_class`` blocks to attach your own classes:

.. code-block:: twig

    {{ ux_breadcrumb({class: 'my-trail'}, theme: '@App/breadcrumb.html.twig') }}

.. code-block:: twig

    {# templates/breadcrumb.html.twig #}
    {% extends '@UXBreadcrumb/theme/default.html.twig' %}

    {% block list_class %}flex items-center gap-2{% endblock %}
    {% block current_class %}font-medium{% endblock %}
    {% block plain_class %}text-gray-400{% endblock %}

Carrying your own data on a crumb
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

``extra`` is never read by the bundle.
It is handed straight to the resolved item, so a template can do whatever it likes with it, such as an icon name or a CSS class::

    #[Breadcrumb(
        label: 'product.index.breadcrumb',
        route: 'product_index',
        extra: ['icon' => 'tabler:package'],
    )]

.. code-block:: html+twig

    {% block item_label %}
        {% if item.extra.icon is defined %}<twig:ux:icon name="{{ item.extra.icon }}" />{% endif %}
        {{ item.label }}
    {% endblock %}

Absolute URLs for JSON-LD
~~~~~~~~~~~~~~~~~~~~~~~~~

``ux_breadcrumb_items(absolute: true)`` gives **every** crumb a URL, including the
current page, which is what schema.org's ``BreadcrumbList`` needs. When the current
page's crumb declares no route, it falls back to the trail's own matched route.

The two modes resolve and memoize independently, so a page that renders both the
visible bar and the JSON-LD node pays for one resolution each:

.. code-block:: html+twig

    {% set items = ux_breadcrumb_items(absolute: true) %}

    {# A single crumb is not a hierarchy, so it is not worth marking up. #}
    {% if items|length > 1 %}
        <script type="application/ld+json">
            {{- {
                '@context': 'https://schema.org',
                '@type': 'BreadcrumbList',
                itemListElement: items|map((item, index) => {
                    '@type': 'ListItem',
                    position: index + 1,
                    name: item.label,
                    item: item.url,
                }),
            }|json_encode|raw -}}
        </script>
    {% endif %}

Root crumbs
-----------

Application-wide crumbs, such as a "Home" or a section root, belong in a ``RootCrumbProviderInterface``.
Providers run on every main request and whatever they yield is prepended to the trail.
Yielding nothing opts a route hierarchy out::

    // src/Breadcrumb/SectionRootCrumbProvider.php
    namespace App\Breadcrumb;

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
    use Symfony\UX\Breadcrumb\RootCrumbProviderInterface;

    final class SectionRootCrumbProvider implements RootCrumbProviderInterface
    {
        public function __invoke(string $route, Request $request): iterable
        {
            if (str_starts_with($route, 'app_admin_')) {
                yield new Breadcrumb(label: 'admin.home.breadcrumb', route: 'app_admin_home');
            }

            // The public site has no breadcrumb bar on its home page,
            // so that page gets no trail at all.
            if (str_starts_with($route, 'app_website_') && 'app_website_home' !== $route) {
                yield new Breadcrumb(label: 'website.home.breadcrumb', route: 'app_website_home');
            }
        }
    }

Implementations are autoconfigured.
Several providers can be registered, and they run in tag priority order.
Zero providers is a fully supported setup.
Yield more than one crumb to prepend a multi-level root.

Providers are called even when the controller declared no crumb of its own, so a
lone root crumb can be the intended breadcrumb of a section's home page.

Expression functions
--------------------

The bundle owns its own ``ExpressionLanguage`` service, backed by the cache pool
described in `Caching and performance`_.

Add functions to it with a tagged provider:

.. code-block:: yaml

    # config/services.yaml
    services:
        App\Breadcrumb\QueryExpressionLanguageProvider:
            tags: ['ux_breadcrumb.expression_function_provider']

The tag is deliberately not autoconfigured: ``ExpressionFunctionProviderInterface``
is also implemented for the routing and security expression languages, and tagging
every one of them here would be wrong. Point the ``expression_language`` option at
your own service id to replace the whole service.

The built-in ``enum()`` function is available. Because the lexer unescapes string
literals, a fully-qualified class name needs four backslashes in PHP source::

    #[Breadcrumb(
        label: 'invitation.index.breadcrumb',
        route: 'invitation_index',
        computedParameters: ['type' => 'enum("App\\\\Enum\\\\FilterType::Guest").value'],
    )]

Caching and performance
-----------------------

Resolving a trail is one ``trans()`` and one URL generation per crumb, and it only happens when a template asks for the crumbs.
There is nothing else to cache, and the bundle deliberately does not try.

Two caches do exist:

* **Parsed crumb expressions** live in ``.ux_breadcrumb.cache``, a child of ``cache.system``. Expressions are static strings, so their parse trees are node-local and deploy-scoped, which beats a shared cache round-trip. Swap the whole ``ExpressionLanguage`` service with the ``expression_language`` option to take this over.
* **Resolution within one request** is memoized per reference type and per locale, so a page that renders both the visible bar and the JSON-LD node resolves the trail twice rather than four times. The memo is keyed on the trail itself, which lives and dies with its request, so nothing leaks between the requests a worker serves.

Resolved crumbs are **not** cached across requests, on purpose.
A label built with ``translationParameters`` embeds live entity state, so a correct cache key would have to evaluate the crumb expressions first, which is the very work the cache was meant to skip.
Keying on the route and its parameters instead would serve a renamed product under its old name.
URLs have the same problem: they depend on the evaluated ``computedParameters``, the reference type, and, in absolute mode, the request's host and scheme.

There is no cache warmer either.
``ExpressionLanguage`` keys a parse tree on the expression *and* its variable names, and the bundle narrows those names per request from the controller arguments a crumb actually references, so the key cannot be computed ahead of time.
Nor is the controller list fully known at warmup: controllers registered as services or declared as closures resolve only through the container, fragment sub-requests have no route at all, and root crumbs come from providers that are handed a live ``Request``.

Finally, the bundle sets no HTTP cache headers and takes no view on page caching.
That is the application's call, and a trail carrying per-entity or per-user labels is exactly what should stay out of a shared HTTP cache.

Crumbs only known at runtime
----------------------------

Inject ``BreadcrumbTrailProvider`` to append to the trail the listener already
built::

    // src/Controller/ProductViewController.php
    namespace App\Controller;

    use App\Entity\Product;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
    use Symfony\UX\Breadcrumb\BreadcrumbTrailProvider;

    final class ProductViewController
    {
        public function __invoke(Product $product, BreadcrumbTrailProvider $trailProvider): Response
        {
            $trailProvider->getTrail()?->append(new Breadcrumb(
                label: $product->getName(),
                translationDomain: false,
            ));

            // ...
        }
    }

Configuration
-------------

.. code-block:: yaml

    # config/packages/ux_breadcrumb.yaml
    ux_breadcrumb:
        # Request attribute the collected trail is stored on
        request_attribute: '_breadcrumbs'

        # Translation domain for crumbs that declare none;
        # null uses the translator's own default
        translation_domain: ~

        # Service id of the ExpressionLanguage used to evaluate crumb expressions
        expression_language: 'ux_breadcrumb.expression_language'

        # Twig template used by ux_breadcrumb()
        theme: '@UXBreadcrumb/theme/default.html.twig'

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as the
Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html
