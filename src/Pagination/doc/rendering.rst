Rendering and customization
===========================

The ``ux_pagination()`` Twig function renders any pagination result:

.. code-block:: twig

    {# templates/product/index.html.twig #}
    {{ ux_pagination(products) }}

The same theme adapts to the result. Numbered pagination renders page links.
Lookahead and cursor pagination render only the previous and next links that
exist. You do not select a different theme for each pagination strategy.

If UX TwigComponent is installed, equivalent component syntax is available:

.. code-block:: html+twig

    <twig:ux:pagination :pagination="products" />

Start with what you want to change
----------------------------------

Choose the smallest customization surface that owns the change:

=================================== ============================================
If you want to...                   Do this
=================================== ============================================
Render usable neutral pagination    Call ``ux_pagination(products)``
Use Bootstrap 5                     Pass the Bootstrap theme template
Use Tailwind CSS                    Pass the Tailwind theme template
Use shadcn or another design system Pass an application-owned Twig theme
Add classes to one pagination       Pass attribute options on that call
Change classes for every pagination Override the selected bundle theme
Change the visible labels           Override the bundle translations
Change an arrow or one label's HTML Override a ``*_label`` theme block
Add first/last numbered controls     Override ``navigation`` and reuse its blocks
Change one part of the HTML         Extend the theme and override a block
Own the complete HTML structure     Pass an application-owned Twig theme
Recolor the default theme           Override the ``--ux-pagination-*`` variables
Add LiveComponent interactions      Pass ``this.paginationLinkAttributes``
Keep ordinary page navigation       Do nothing: the output uses real links
=================================== ============================================

Attribute options decorate existing markup. Theme blocks replace one
structural region. An application theme owns the complete rendering. The
LiveComponent integration changes link behavior, not the selected theme.

The public option is named ``theme``, following Symfony Form terminology. Its
value is always one complete Twig template name. UX Pagination does not add an
alias or theme-composition language on top of Twig.

Built-in themes
---------------

``@UXPagination/theme/default.html.twig``
    Semantic markup with stable ``ux-pagination`` classes. The bundle
    stylesheet is optional.

``@UXPagination/theme/bootstrap.html.twig``
    Bootstrap 5 structure and classes. The bundle does not install Bootstrap.

``@UXPagination/theme/tailwind.html.twig``
    Tailwind utility classes. The bundle does not install Tailwind. Register
    the bundle templates as a Tailwind source so these classes are generated.

All three themes support numbered, lookahead and cursor results. They keep
real links and do not require Stimulus, Turbo or LiveComponent.

When a result has neither a previous nor a next link, the built-in themes
render nothing, including the optional summary. Render an application summary
outside ``ux_pagination()`` when it must remain visible for a single page.

Select a theme for one rendering:

.. code-block:: twig

    {{ ux_pagination(
        products,
        theme: '@UXPagination/theme/bootstrap.html.twig',
    ) }}

Or set the application default:

.. code-block:: yaml

    # config/packages/ux_pagination.yaml
    ux_pagination:
        theme: '@UXPagination/theme/bootstrap.html.twig'

A ``theme`` passed to ``ux_pagination()`` or ``<twig:ux:pagination>``
overrides this configured default for that rendering only.

The option accepts one template, not a list. Compose several customization
layers with normal Twig inheritance in an application-owned theme:

.. code-block:: html+twig

    {# templates/pagination/application.html.twig #}
    {% extends '@UXPagination/theme/bootstrap.html.twig' %}

    {% block info %}
        <p class="results-summary">{{ pagination.info }}</p>
    {% endblock %}

Then select that theme in configuration or for one rendering:

.. code-block:: yaml

    # config/packages/ux_pagination.yaml
    ux_pagination:
        theme: 'pagination/application.html.twig'

Tailwind source detection
~~~~~~~~~~~~~~~~~~~~~~~~~

Tailwind does not automatically scan dependencies under ``vendor/``. When
using Tailwind 4, register the bundled theme from your main stylesheet:

.. code-block:: css

    /* assets/styles/app.css */
    @import "tailwindcss";
    @source "../../vendor/symfony/ux-pagination/templates";

With Tailwind 3, add the same directory to the ``content`` paths:

.. code-block:: javascript

    // tailwind.config.js
    export default {
        content: [
            './templates/**/*.html.twig',
            './vendor/symfony/ux-pagination/templates/**/*.html.twig',
        ],
    };

An application-owned theme under ``templates/`` is normally covered by the
application's existing source configuration.

Attribute options
-----------------

Three attribute groups cover the common extension points. The built-in themes
apply them to these elements; an application theme receives the same values
and decides where to render them:

========================== ================================================
Option                     Target
========================== ================================================
``attributes``             Root ``<nav>`` element
``navigation_attributes``  Inner list containing the controls
``link_attributes``        Previous, numbered and next links
========================== ================================================

The built-in themes append ``class`` values to their own classes. Any other
attribute replaces the theme default with the same name. The renderer validates
and passes these values but does not decide how an application theme applies
them:

.. code-block:: twig

    {{ ux_pagination(
        products,
        attributes: {
            id: 'product-pages',
            class: 'product-pagination',
            'data-controller': 'catalog-navigation',
        },
        navigation_attributes: {
            class: 'product-pagination__controls',
        },
        link_attributes: {
            class: 'product-pagination__link',
            'data-testid': 'pagination-link',
        },
    ) }}

In component syntax, regular component attributes decorate the root element.
The explicit ``attributes`` map is also accepted. When both define the same
attribute, the regular component attribute wins:

.. code-block:: html+twig

    <twig:ux:pagination
        :pagination="products"
        class="product-pagination"
        :navigationAttributes="{class: 'product-pagination__controls'}"
        :linkAttributes="{class: 'product-pagination__link'}"
    />

Dynamic link attributes
-----------------------

``link_attributes`` also accepts a PHP closure. It runs once for each link and
receives this context:

================= ===========================================================
Key               Value
================= ===========================================================
``relation``      ``previous``, ``page`` or ``next``
``url``           URL owned by the pagination result
``page``          One-based page number, or ``null`` for cursor pagination
``cursor``        Opaque cursor, or ``null`` for numbered pagination
================= ===========================================================

Use it when an interaction layer needs link-specific data and the renderer
should not know about that library::

    return $this->render('product/index.html.twig', [
        'products' => $pagination,
        'paginationLinkAttributes' => static fn (array $link): array => [
            'data-page' => $link['page'],
            'data-relation' => $link['relation'],
        ],
    ]);

Pass the closure as a normal Twig variable:

.. code-block:: twig

    {{ ux_pagination(
        products,
        link_attributes: paginationLinkAttributes,
    ) }}

The LiveComponent integration uses this same extension point. See
:doc:`live-component`.

Attribute safety
----------------

The renderer validates attribute names and value types before rendering.
The built-in themes use normal Twig escaping, support boolean attributes and do
not render attribute values through ``raw``. ``link_attributes`` cannot replace
``href``: the pagination result keeps ownership of navigation URLs.

An application theme owns its HTML and must keep Twig autoescaping enabled when
it renders these values.

Attribute options are an HTML API, not an HTML sanitizer. Keep attribute names
under application control and do not create event-handler attributes from
request data.

Keep the theme name under application control as well. Do not pass a template
name read directly from request data.

Override the default theme
--------------------------

To change the markup for every rendering, use a standard Symfony bundle
template override. Copy the theme template to:

.. code-block:: text

    templates/
    └── bundles/
        └── UXPaginationBundle/
            └── theme/
                └── default.html.twig

The application now owns the default markup. No bundle configuration is
needed.

For a smaller override, extend the original bundle theme through its
non-overridable namespace and replace one of its structural blocks:

.. code-block:: html+twig

    {# templates/bundles/UXPaginationBundle/theme/default.html.twig #}
    {% extends '@!UXPagination/theme/default.html.twig' %}

    {% block info %}
        <p class="results-summary">
            {{ 'product.results'|trans({count: pagination|length}) }}
        </p>
    {% endblock %}

Themes render like ordinary Twig templates. The built-in themes call their
structural and label blocks from ``pagination``; helper block definitions do
not emit additional output. A standalone application theme can remain a plain
Twig template and does not need to define any block.

The built-in themes expose these blocks:

================== ==========================================================
Block              Owns
================== ==========================================================
``pagination``     Complete rendering and visibility condition
``info``           Result summary
``navigation``     Inner controls container
``previous``       Previous control
``pages``          Numbered pages, current page and gaps
``next``           Next control
``previous_label`` Previous control content: arrow and label
``next_label``     Next control content: label and arrow
``page_label``     Content of a page link and of the current page
``attr``           HTML attribute rendering for the ``attr`` variable
================== ==========================================================

Override a ``*_label`` block for a small content change. This replaces the
left arrow with a chevron on every previous control, links and disabled
state alike:

.. code-block:: twig

    {# templates/bundles/UXPaginationBundle/theme/default.html.twig #}
    {% extends '@!UXPagination/theme/default.html.twig' %}

    {% block previous_label %}&lsaquo; {{ 'Previous'|trans({}, 'UXPaginationBundle') }}{% endblock %}

Override ``pages`` when each page item needs different markup. Override
``pagination`` or use an application theme when the complete structure is
different.

Use an application theme
------------------------

Pass a normal Twig path when a design system owns the complete markup:

.. code-block:: twig

    {{ ux_pagination(
        products,
        theme: 'pagination/product.html.twig',
    ) }}

A configured Twig namespace works too:

.. code-block:: twig

    {{ ux_pagination(
        products,
        theme: '@App/pagination/product.html.twig',
    ) }}

The value goes directly to Twig. Relative application paths, bundle
namespaces and custom namespaces follow the same lookup rules as any other
Twig template.

Every custom theme receives:

============================== ==============================================
Value                          Meaning
============================== ==============================================
``pagination``                 Iterable result and navigation state
``numbered``                   Whether numbered pages are available
``attributes``                 Validated root attributes
``navigation_attributes``      Validated controls attributes
``link_attributes.previous``   Resolved previous-link attributes
``link_attributes.pages``      Resolved attributes indexed by page number
``link_attributes.next``       Resolved next-link attributes
``show_info``                  Whether the summary was requested
============================== ==============================================

``link_attributes.pages`` contains only numbered pages rendered as links. The
current page and gaps have no link attributes.

Twig context variables and named arguments passed to ``ux_pagination()`` use
``snake_case``. Twig component properties use ``camelCase``:

.. code-block:: twig

    {{ ux_pagination(
        products,
        show_info: false,
        navigation_attributes: {class: 'product-pagination__controls'},
    ) }}

.. code-block:: html+twig

    <twig:ux:pagination
        :pagination="products"
        :showInfo="false"
        :navigationAttributes="{class: 'product-pagination__controls'}"
    />

Numbered results expose ``pagination.pages``. Each entry provides ``page``,
``url``, ``isCurrent`` and ``isGap``. Every result provides
``hasPrevious``, ``previousUrl``, ``hasNext``, ``nextUrl`` and ``info``.

Custom themes own their markup. Preserve the navigation landmark,
``aria-current``, accessible labels and ``rel=prev`` or ``rel=next`` when they
apply.

Translate the result summary
----------------------------

Messages use the ``UXPaginationBundle`` domain. Every message id is the
natural English sentence. Without the Translation component, an identity
translator renders the ids as-is: the interface stays readable in English,
including ``%count%`` pluralization. The bundle ships ten locales.

An application can override any message in its normal translation catalog
without bundle configuration:

.. code-block:: yaml

    # translations/UXPaginationBundle.fr.yaml
    'Showing %start%-%end% of %total%': "Affichage de %start% à %end% sur %total%"
    'No items': "Aucun résultat"

The complete message list:

``Showing %start%-%end% of %total%``
    Numbered summary with an exact total.
``Showing %start%-%end%``
    Lookahead summary without a total.
``No items``
    Empty result summary, shared by every strategy.
``Showing %count% item|Showing %count% items``
    Cursor summary, pluralized through ``%count%``.
``Showing %count% item (last page)|Showing %count% items (last page)``
    Final cursor page, pluralized through ``%count%``.
``Previous`` and ``Next``
    Visible navigation labels.
``Previous page`` and ``Next page``
    Accessible previous/next labels.
``Go to page %page%``
    Accessible numbered-link label.
``Pagination``
    Navigation landmark label.

Hide the built-in summary when one screen needs application-specific wording:

.. code-block:: html+twig

    {{ ux_pagination(products, show_info: false) }}

    <p>{{ 'product.results.summary'|trans({
        '%first%': products.firstItemNumber,
        '%last%': products.lastItemNumber,
        '%total%': products.totalItems,
    }) }}</p>

Customize the default stylesheet
--------------------------------

The optional default stylesheet exposes these custom properties:

.. code-block:: css

    .ux-pagination.product-pagination {
        --ux-pagination-color: #172033;
        --ux-pagination-muted-color: #667085;
        --ux-pagination-border-color: #cfd4dc;
        --ux-pagination-background: #ffffff;
        --ux-pagination-active-background: #6d28d9;
        --ux-pagination-active-color: #ffffff;
        --ux-pagination-focus-color: #7c3aed;
    }

It also supplies dark-color-scheme defaults. Applications that own their
light and dark modes can override the variables inside their own theme scope.

Browser and metadata contract
-----------------------------

The built-in themes render semantic HTML and real links. UX Pagination
ships no browser controller, does not fetch pages and does not prefetch them.
Stimulus, Turbo and LiveComponent are optional application-level enhancements.

UX Pagination does not emit a canonical link or redirect automatically.
Filters, alternate routes and indexing rules determine canonical policy, so
that decision belongs to the application. Use ``previousUrl`` and ``nextUrl``
for HTML or HTTP metadata when appropriate.
