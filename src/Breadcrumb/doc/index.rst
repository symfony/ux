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

Put a crumb on each action, and name the action whose trail goes above it as its ``parent``.
The crumbs of the parent go above the crumb that names it, and so on up the chain, so each action declares only its own level::

    // src/Controller/ProductController.php
    namespace App\Controller;

    use App\Entity\Product;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;

    #[Route('/products', name: 'product_')]
    final class ProductController extends AbstractController
    {
        #[Route('', name: 'index')]
        #[Breadcrumb(label: 'Products', route: 'product_index')]
        public function index(): Response
        {
            return $this->render('product/index.html.twig');
        }

        #[Route('/{slug}', name: 'view')]
        #[Breadcrumb(
            label: '{name:product}',
            route: 'product_view',
            parameters: ['slug'],
            parent: [self::class, 'index'],
        )]
        public function view(Product $product): Response
        {
            return $this->render('product/view.html.twig');
        }

        #[Route('/{slug}/edit', name: 'edit')]
        #[Breadcrumb(label: 'Edit', parent: [self::class, 'view'])]
        public function edit(Product $product): Response
        {
            return $this->render('product/edit.html.twig');
        }
    }

.. code-block:: twig

    {# templates/base.html.twig #}
    {{ ux_breadcrumb() }}

The depth of the trail comes from the chain, so the three actions above produce:

===========  ===========================================================
Action       Trail
===========  ===========================================================
``index``    Products
``view``     Products, then the product
``edit``     Products, then the product, then Edit
===========  ===========================================================

The last crumb is the current page, which is never a link.
Every other crumb is an ancestor, and an ancestor is a link, so ``index`` and ``view`` declare a ``route``, and ``view`` the URL parameters it needs.
On their own page, the ``route`` and ``parameters`` cost nothing.

Parent crumbs
~~~~~~~~~~~~~

A parent is one of:

* an action, written as an array such as ``[ProductController::class, 'view']``, or ``[self::class, 'view']`` for an action of the same controller
* an invokable controller class, such as ``ProductViewController::class``
* a route name, such as ``'product_view'``, resolved to the controller of that route

Invokable controllers chain the same way::

    // src/Controller/ProductViewController.php
    #[Breadcrumb(label: '{name:product}', route: 'product_view', parameters: ['slug'], parent: 'product_index')]
    final class ProductViewController extends AbstractController
    {
        #[Route('/products/{slug}', name: 'product_view')]
        public function __invoke(Product $product): Response
        {
            return $this->render('product/view.html.twig');
        }
    }

    // src/Controller/ProductEditController.php
    #[Breadcrumb(label: 'Edit', parent: ProductViewController::class)]
    final class ProductEditController extends AbstractController
    {
        #[Route('/products/{slug}/edit', name: 'product_edit')]
        public function __invoke(Product $product): Response
        {
            return $this->render('product/edit.html.twig');
        }
    }

The ancestors are resolved against the current request, like any other crumb of the trail.
Their placeholders and expressions read the arguments of the current action, so they only resolve when that action has arguments with the same names.
In both examples, the edit action receives a ``$product`` too, so the product's crumb shows its name on the edit page as well.

A cycle, a parent that points at nothing, and a parent on any crumb but the first of its level throw a ``LogicException`` when the page is requested.
Resolving a route name reads the route collection, which is expensive, so the map of routes to controllers is kept in the ``.ux_breadcrumb.cache`` pool.
In debug mode it is only kept for the current process, so a changed route is picked up at once.
A route whose controller is a service id rather than a class cannot be followed; name the controller class instead.

Several crumbs on one controller
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The attribute is repeatable, and targets both classes and methods.
Declare the crumbs in trail order, top to bottom: class-level crumbs always come before the action's own, so a controller can put the shared head of the trail on the class and let each action add its own leaves::

    #[Route('/products', name: 'product_')]
    #[Breadcrumb(label: 'Products', route: 'product_index')]
    final class ProductController extends AbstractController
    {
        #[Route('', name: 'index')]
        public function index(): Response
        {
            // ...
        }

        #[Route('/{slug}', name: 'view')]
        #[Breadcrumb(label: '{name:product}')]
        public function view(Product $product): Response
        {
            // ...
        }

        #[Route('/{slug}/edit', name: 'edit')]
        #[Breadcrumb(label: '{name:product}', route: 'product_view', parameters: ['slug'])]
        #[Breadcrumb(label: 'Edit')]
        public function edit(Product $product): Response
        {
            // ...
        }
    }

This produces the same trails as the example of `Usage`_.
``index`` declares no crumb of its own, so it gets the class trail alone, and ``edit`` declares two, because an action may add more than one level below the shared head.
An invokable controller puts all of its crumbs on the class.

The two styles mix.
When an action's crumb names a parent, the class-level crumbs are left out for that action, since the parent's own trail already starts with them.
A parent on a class-level crumb applies to every action of the controller.
Only the first crumb of a class or of a method can name a parent.

The attribute
-------------

* ``label`` (``string|Expression``): the label, such as ``'Products'``, a label computed from the controller arguments, with placeholders such as ``'{name:product}'`` or an ``Expression``, see `Computed labels`_, or a translation key, see `Translated labels`_
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

A crumb that shows an entity's own name, such as a product page, writes placeholders in its ``label``, with the syntax of a ``#[Route]`` path::

    #[Breadcrumb(label: '{name:product}')]

=========================  ===========================================================================
Placeholder                Value
=========================  ===========================================================================
``{slug}``                 the controller argument ``$slug``, or else the route parameter ``slug``
``{name:product}``         the property ``name`` of the controller argument ``$product``
``{title:product.name}``   the property path ``name`` of ``$product``; ``title`` only names the placeholder
=========================  ===========================================================================

Properties are read with the PropertyAccess component, so ``{name:product}`` calls ``getName()`` on a Doctrine entity whose ``$name`` is private.
A placeholder can sit anywhere in the label, such as ``'Edit {name:product}'``.

A label with placeholders is a pattern, like a route path: it is the label itself, not a translation key, so it needs no ``translationDomain: false``.
It therefore takes no ``translationDomain`` and no ``translationParameters``: passing either throws an ``InvalidArgumentException``.
A label that must be translated keeps its translation key and passes the values through ``translationParameters``, see `Translated labels`_.

A placeholder that cannot be read, or whose value is neither a scalar nor a ``Stringable``, is replaced by an empty string rather than taking the page down.
This happens to an ancestor whose placeholder names an argument the current action does not have.

For anything a placeholder cannot say, such as a concatenation or a translated enum, give an ``Expression`` as the ``label``.
It is evaluated against the controller's arguments, like the other expressions of the crumb::

    #[Breadcrumb(label: new Expression('product.brand.name ~ " " ~ product.name'))]

Its value is the label itself too, unless it is a ``TranslatableInterface``, such as a ``TranslatableMessage`` or a translatable enum, which is translated.
The same rules apply: no ``translationDomain``, no ``translationParameters``, and an empty label when it cannot be evaluated.
A placeholder whose value is a ``TranslatableInterface`` is translated the same way.

Translated labels
~~~~~~~~~~~~~~~~~

A label without placeholders goes through the translator, in the default domain unless ``translationDomain`` names another.
The translator returns a label it has no translation for unchanged, so ``'Products'`` renders as is.
A translated application gives a translation key instead, and passes the values of the message through ``translationParameters``::

    #[Breadcrumb(
        label: 'product.view.breadcrumb',
        translationParameters: ['name' => new Expression('product.name')],
    )]

.. code-block:: yaml

    # translations/messages+intl-icu.en.yaml
    product.view.breadcrumb: 'Product {name}'

``translationDomain: false`` skips the translator altogether, for a label that must never be taken for a translation key, such as one built from user data.

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

    #[Breadcrumb(label: 'Categories', route: 'category_index')]
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
        label: 'Products',
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
                yield new Breadcrumb(label: 'Admin', route: 'app_admin_home');
            }

            // The public site has no breadcrumb bar on its home page,
            // so that page gets no trail at all.
            if (str_starts_with($route, 'app_website_') && 'app_website_home' !== $route) {
                yield new Breadcrumb(label: 'Home', route: 'app_website_home');
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
        label: 'Invitations',
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
