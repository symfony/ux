Symfony UX Breadcrumb
=====================

.. caution::

    **EXPERIMENTAL** This bundle is currently experimental and is likely to
    change, possibly significantly, before its first stable release.

Symfony UX Breadcrumb declares the breadcrumb trail of a page on its controller,
with a repeatable ``#[Breadcrumb]`` attribute. The trail is collected unresolved
onto the request, and only turned into labels and URLs when a template asks for
it.

What the attribute cannot state up front, such as a trail whose depth is only known
once the entities are loaded, a controller adds to the collected trail itself. The two
are peers: see `Building the trail at runtime`_.

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

Declare the crumbs in trail order, top to bottom.
The attribute targets both classes and methods, so a controller with several actions puts the shared head of the trail on the class and lets each action add its own leaves::

    // src/Controller/ProductController.php
    namespace App\Controller;

    use App\Entity\Product;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\ExpressionLanguage\Expression;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;

    #[Route('/products', name: 'product_')]
    #[Breadcrumb(label: 'product.index.breadcrumb', route: 'product_index')]
    final class ProductController extends AbstractController
    {
        #[Route('', name: 'index')]
        public function index(): Response
        {
            return $this->render('product/index.html.twig');
        }

        #[Route('/{slug}', name: 'view')]
        #[Breadcrumb(label: new Expression('product.name'))]
        public function view(Product $product): Response
        {
            return $this->render('product/view.html.twig');
        }

        #[Route('/{slug}/edit', name: 'edit')]
        #[Breadcrumb(label: new Expression('product.name'), route: 'product_view', parameters: ['slug'])]
        #[Breadcrumb(label: 'product.edit.breadcrumb')]
        public function edit(Product $product): Response
        {
            return $this->render('product/edit.html.twig');
        }
    }

.. code-block:: twig

    {# templates/product/view.html.twig #}
    {{ ux_breadcrumb() }}

Class-level crumbs always come before the action's own, so the three actions above produce:

===========  ===========================================================
Action       Trail
===========  ===========================================================
``index``    Products
``view``     Products, then the product
``edit``     Products, then the product, then Edit
===========  ===========================================================

``index`` declares no crumb of its own, so it gets the class trail alone, which is usually what a section index page wants.
``edit`` declares two, because an action may add more than one level below the shared head.

An invokable controller works the same way, with everything on the class since there is only one action::

    // src/Controller/ProductViewController.php
    namespace App\Controller;

    use App\Entity\Product;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\ExpressionLanguage\Expression;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;

    #[Breadcrumb(label: 'product.index.breadcrumb', route: 'product_index')]
    #[Breadcrumb(label: 'product.view.breadcrumb', translationParameters: ['name' => new Expression('product.name')])]
    final class ProductViewController extends AbstractController
    {
        #[Route('/products/{slug}', name: 'product_view')]
        public function __invoke(Product $product): Response
        {
            return $this->render('product/view.html.twig');
        }
    }

Parent crumbs
~~~~~~~~~~~~~

Sibling invokable controllers would each repeat the ancestry they share.
Name a ``parent`` instead: the crumbs of that controller go above the crumb that names it, and so on up the chain.
Each controller then declares only its own crumb, and the depth of the trail comes from the chain::

    // src/Controller/ProductEditController.php
    #[Breadcrumb(label: 'product.edit.breadcrumb', parent: ProductViewController::class)]
    final class ProductEditController extends AbstractController
    {
        #[Route('/products/{slug}/edit', name: 'product_edit')]
        public function __invoke(Product $product): Response
        {
            return $this->render('product/edit.html.twig');
        }
    }

With the ``ProductViewController`` above, this page gets Products, then the product, then Edit.

A parent is one of:

* an invokable controller class, such as ``ProductViewController::class``
* an action, written as an array such as ``[ProductController::class, 'view']``, or ``[self::class, 'view']`` for an action of the same controller
* a route name, such as ``'product_view'``, resolved to the controller of that route

It works the same way in a controller with several actions::

    #[Route('/products', name: 'product_')]
    #[Breadcrumb(label: 'product.index.breadcrumb', route: 'product_index')]
    final class ProductController extends AbstractController
    {
        #[Route('/{slug}', name: 'view')]
        #[Breadcrumb(label: 'product.view.breadcrumb', route: 'product_view', parameters: ['slug'])]
        public function view(Product $product): Response
        {
            // ...
        }

        #[Route('/{slug}/edit', name: 'edit')]
        #[Breadcrumb(label: 'product.edit.breadcrumb', parent: [self::class, 'view'])]
        public function edit(Product $product): Response
        {
            // ...
        }
    }

When an action's crumb names a parent, the class-level crumbs are left out for that action.
The parent's own trail already starts with them, so ``edit`` gets Products once, then the product, then Edit.
A parent on a class-level crumb applies to every action of the controller.
Only the first crumb of a class or of a method can name a parent.

The ancestors are resolved against the current request, like any other crumb of the trail.
As an ancestor, a crumb is a link, so give it a ``route`` and the URL parameters it needs.
Its expressions are evaluated against the arguments of the current action, so they only resolve when that action has arguments with the same names.
The ``view`` crumb above declares ``route`` and ``parameters`` for that reason: on its own page, the current crumb is not a link anyway, so they cost nothing there.

A cycle, a parent that points at nothing, and a parent on any crumb but the first of its level throw a ``LogicException`` when the page is requested.
Resolving a route name reads the route collection, which is expensive, so the map of routes to controllers is kept in the ``.ux_breadcrumb.cache`` pool.
In debug mode it is only kept for the current process, so a changed route is picked up at once.
A route whose controller is a service id rather than a class cannot be followed; name the controller class instead.

The attribute
-------------

* ``label`` (``string|Expression``): the translation key, the literal label when ``translationDomain`` is ``false``, or an ``Expression`` computing the label, see `Computed labels`_
* ``route`` (``?string``): name of the route to link to
* ``parameters`` (``array<int|string, mixed>``): the URL parameters, inherited from the matched route, given, or computed, see below
* ``translationDomain`` (``string|false|null``): ``null`` for the default domain, a domain name, or ``false`` to skip translation
* ``translationParameters`` (``array<string, mixed>``): the translator's parameters, given or computed
* ``extra`` (``array<string, mixed>``): arbitrary data forwarded untouched to the resolved item, never read by the bundle
* ``parent`` (``string|array{class-string, string}|null``): the controller class, action or route name whose trail goes above this crumb, see `Parent crumbs`_

Each entry of ``parameters`` says where its value comes from:

* a **bare name**, such as ``'slug'``, takes the value from the already-matched route (``_route_params``).
* an ``Expression`` is evaluated against the controller's arguments.
* **any other value** is used as given. Nothing is evaluated, so this is how a constant is passed, and how a crumb built in PHP passes the values it already holds.

``translationParameters`` follows the same rules, minus the bare names, and feeds the translator rather than the URL.

::

    use Symfony\Component\ExpressionLanguage\Expression;

    #[Breadcrumb(
        label: 'product.view.breadcrumb',
        route: ProductRouteName::View->value,
        parameters: [
            'slug',
            'page' => 1,
            'state' => new Expression('product.state'),
        ],
        translationParameters: ['name' => new Expression('product.name')],
    )]

A route name is a string, as everywhere else in Symfony.
If your application keeps its route names in a backed enum, pass the case's ``->value``, which is a valid constant expression in an attribute argument.

The form of an entry does not decide whether a parameter lands in the path or in the query string.
The URL generator places each name in the path when the route declares a placeholder for it, and in the query string otherwise, so in the example above ``slug`` fills the ``{slug}`` placeholder while ``page`` and ``state`` land in the query string.
A name given both bare and with a value takes the value.

Only the controller arguments a crumb expression actually names are kept on the trail, so the whole argument list, and notably the ``Request``, is not pinned into the request attributes until render time.

Resolution degrades rather than throwing.
A crumb pointing at an unknown route, at one whose required parameters are missing, or carrying an expression that cannot be evaluated against this action's arguments, resolves to ``url === null`` and renders as plain text.
A translation parameter that cannot be evaluated leaves its placeholder in the label rather than taking the page down.

Computed labels
~~~~~~~~~~~~~~~

A crumb that shows an entity's own name, such as a product page, gives an ``Expression`` as its ``label``.
It is evaluated against the controller's arguments, like the other expressions of the crumb::

    #[Breadcrumb(label: new Expression('product.name'))]

The value is the label itself, not a translation key, so it needs no ``translationDomain: false``.
A value that should be translated is returned as a ``TranslatableInterface``, such as a ``TranslatableMessage`` or a translatable enum, and the translator is handed to it.
An ``Expression`` label therefore takes no ``translationDomain`` and no ``translationParameters``: passing either throws an ``InvalidArgumentException``.

An ``Expression`` label that cannot be evaluated, or whose value is neither a scalar, a ``Stringable`` nor a ``TranslatableInterface``, resolves to an empty label rather than taking the page down.
This happens to an ancestor whose expression names an argument the current action does not have.

Building the trail at runtime
-----------------------------

A trail whose depth is only known once the entities are loaded cannot be written as a
fixed list of attributes. Inject ``BreadcrumbTrailProvider``, ask it for the trail the
listener already built, and add to it::

    // src/Controller/CategoryController.php
    namespace App\Controller;

    use App\Entity\Category;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
    use Symfony\UX\Breadcrumb\BreadcrumbTrailProvider;

    #[Breadcrumb(label: 'category.index.breadcrumb', route: 'category_index')]
    final class CategoryController extends AbstractController
    {
        #[Route('/categories/{slug}', name: 'category_view')]
        public function view(Category $category, BreadcrumbTrailProvider $trailProvider): Response
        {
            $chain = [];
            for ($node = $category; null !== $node; $node = $node->getParent()) {
                $chain[] = new Breadcrumb(
                    label: $node->getName(),
                    route: 'category_view',
                    parameters: ['slug' => $node->getSlug()],
                    translationDomain: false,
                );
            }

            $trailProvider->getTrail()?->append(...array_reverse($chain));

            return $this->render('category/view.html.twig');
        }
    }

The class attribute states the part of the trail that never changes, and the controller
adds as many levels as the category happens to be deep. Every level of the chain is built
the same way, the deepest one included, which changes nothing on the page: the current
crumb is never a link.

``getTrail()`` returns ``null`` when there is no trail to add to, outside a request or
before the listener has run, which happens on the controller arguments event. Hence the
``?->``.
It reads the **main** request on purpose, so a crumb appended from a
``{{ render(controller(...)) }}`` fragment lands on the page's trail rather than on one
that nothing renders.

``BreadcrumbTrail`` is a small mutable list of unresolved crumbs:

* ``append(Breadcrumb ...$crumbs)`` adds below what is already there. Root crumbs and the attribute's are collected before the controller runs, so an appended crumb is always deeper than those.
* ``prepend(Breadcrumb ...$crumbs)`` adds above them all, root crumbs included.
* ``all()`` returns the crumbs, and ``isEmpty()`` says whether there are any, for a controller that decides what to add from what is already there.

Both take any number of crumbs, so a variable depth is a loop and a spread, as above.

A crumb built here already holds its values, so it passes them as given in ``parameters``
and a literal label with ``translationDomain: false``. Its ``Expression`` values are evaluated
against the controller arguments the listener captured, which a crumb appended later has no say over.

Adding a crumb stays cheap: nothing is resolved until a template asks for it, and a crumb
appended after a first ``ux_breadcrumb_items()`` call is picked up rather than served from
the memo.

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
        parameters: ['type' => new Expression('enum("App\\\\Enum\\\\FilterType::Guest").value')],
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
URLs have the same problem: they depend on the evaluated ``parameters``, the reference type, and, in absolute mode, the request's host and scheme.

There is no cache warmer either.
``ExpressionLanguage`` keys a parse tree on the expression *and* its variable names, and the bundle narrows those names per request from the controller arguments a crumb actually references, so the key cannot be computed ahead of time.
Nor is the controller list fully known at warmup: controllers registered as services or declared as closures resolve only through the container, fragment sub-requests have no route at all, and root crumbs come from providers that are handed a live ``Request``.

Finally, the bundle sets no HTTP cache headers and takes no view on page caching.
That is the application's call, and a trail carrying per-entity or per-user labels is exactly what should stay out of a shared HTTP cache.

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
