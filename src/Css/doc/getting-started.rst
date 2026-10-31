Getting started
===============

This page declares a few design tokens, styles a template with ``css()`` and
shows what the bundle writes. It assumes the bundle is installed and the
stylesheet is loaded, as shown in :doc:`installation`.

Declare tokens
--------------

Tokens are the values your styles are allowed to use: colors, spacing, radii
and so on. Declare them in the bundle configuration:

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            colors:
                blue: { 500: '#3b82f6', 600: '#2563eb' }
                gray: { 50: '#f9fafb', 900: '#111827' }
            spacing: { sm: '0.5rem', md: '1rem', lg: '2rem' }
            radii: { md: '0.375rem' }
        semantic_tokens:
            colors:
                primary: '{colors.blue.500}'
                fg: { base: '{colors.gray.900}', _dark: '{colors.gray.50}' }

``tokens`` holds raw values, in groups such as ``blue.500``.
``semantic_tokens`` gives them names that describe their role: ``primary``
points to ``blue.500``, and ``fg`` changes value in dark mode. See
:doc:`tokens` for the details.

Style a template
----------------

Call ``css()`` inside a ``class`` attribute, with a hash of properties:

.. code-block:: html+twig

    {# templates/product/show.html.twig #}
    {% extends 'base.html.twig' %}

    {% block body %}
        <article class="{{ css({ p: 'md', rounded: 'md', bg: 'gray.50', color: 'fg' }) }}">
            <h1 class="{{ css({ color: 'primary' }) }}">{{ product.name }}</h1>
        </article>
    {% endblock %}

``p`` is short for ``padding``, and ``rounded`` for ``borderRadius``. Each
value is the name of a token. The page renders these classes:

.. code-block:: html

    <article class="p_md bdr_md bg_gray.50 c_fg">
        <h1 class="c_primary">...</h1>
    </article>

The classes are resolved when Twig compiles the template. Rendering the page
only prints them.

What the bundle writes
----------------------

In dev, the bundle updates ``var/ux_css/styles.css`` before responding to every
request that follows a template change. After the first page load, the file
contains every token as a CSS variable, then one rule per class:

.. code-block:: css

    @layer tokens, utilities;

    @layer tokens {
        :where(:root, :host) {
            --colors-blue-500: #3b82f6;
            --colors-gray-50: #f9fafb;
            --spacing-md: 1rem;
            /* ... */
            --colors-primary: var(--colors-blue-500);
            --colors-fg: var(--colors-gray-900);
        }

        .dark {
            --colors-fg: var(--colors-gray-50);
        }
    }

    @layer utilities {
        .p_md { padding: var(--spacing-md); }
        .bdr_md { border-radius: var(--radii-md); }
        .bg_gray\.50 { background: var(--colors-gray-50); }
        .c_fg { color: var(--colors-fg); }
        .c_primary { color: var(--colors-primary); }
    }

The bundle reads every template of the application, not only the ones you
open. So a template rendered later, for example by a Live Component, finds
its rules in the file. There is no watcher to start.

Add a hover and a breakpoint
----------------------------

Conditions start with an underscore, and breakpoints are written as is:

.. code-block:: html+twig

    <a href="{{ path('product_index') }}" class="{{ css({
        color: 'primary',
        _hover: { color: 'blue.600' },
        p: { base: 'sm', md: 'md' }
    }) }}">Back to the list</a>

This gives ``c_primary hover:c_blue.600 p_sm md:p_md``: the link turns darker
on hover, and its padding grows from ``sm`` to ``md`` on screens at least
``48rem`` wide. :doc:`conditions` lists every condition and breakpoint.

Switch to dark mode
-------------------

The ``fg`` token has a value for the ``_dark`` condition. By default, that
condition applies inside an element that has the ``dark`` class:

.. code-block:: html+twig

    {# templates/base.html.twig #}
    <html lang="en" class="dark">

Every ``color: 'fg'`` now uses ``gray.50``, with no change to the templates.
:doc:`conditions` shows how to use another selector, or the operating system
preference.

Make a mistake
--------------

Misspell a token, and the page shows a Twig error that points to the line:

.. code-block:: html+twig

    <h1 class="{{ css({ color: 'primry' }) }}">{{ product.name }}</h1>

.. code-block:: text

    Unknown colors token "primry". Did you mean "primary" in
    "product/show.html.twig" at line 6?

The same check runs in ``lint:twig``, and ``cache:warmup`` fails on it in the
``prod`` environment, so CI catches the mistake too. :doc:`validation` lists
what is checked.

Next steps
----------

* :doc:`tokens` covers every token category and how to use tokens in
  hand-written CSS.
* :doc:`css-function` is the reference for the ``css()`` syntax.
* :doc:`dynamic-styles` explains values only known at runtime.
