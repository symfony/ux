Getting started
===============

This page declares a few design tokens, styles a template with ``css()`` and
shows what the bundle writes. It assumes the bundle is installed and the
stylesheet is loaded, as shown in :doc:`installation`.

Declare tokens
--------------

Tokens are the values your styles are allowed to use: colors, spacing, radii
and so on. UX CSS reads them from UX Design Tokens, which loads a token file
written in the DTCG format:

.. code-block:: json

    {
        "color": {
            "$type": "color",
            "blue": {
                "500": { "$value": { "colorSpace": "srgb", "components": [0.23, 0.51, 0.96] } },
                "600": { "$value": { "colorSpace": "srgb", "components": [0.15, 0.39, 0.92] } }
            },
            "gray": {
                "50": { "$value": { "colorSpace": "srgb", "components": [0.98, 0.98, 0.98] } },
                "900": { "$value": { "colorSpace": "srgb", "components": [0.07, 0.09, 0.15] } }
            },
            "primary": { "$value": "{color.blue.500}" },
            "fg": { "$value": "{color.gray.900}" }
        },
        "dimension": {
            "$type": "dimension",
            "spacing": {
                "sm": { "$value": { "value": 0.5, "unit": "rem" } },
                "md": { "$value": { "value": 1, "unit": "rem" } },
                "lg": { "$value": { "value": 2, "unit": "rem" } }
            },
            "radius": {
                "md": { "$value": { "value": 0.375, "unit": "rem" } }
            }
        }
    }

.. code-block:: yaml

    # config/packages/ux_design_tokens.yaml
    ux_design_tokens:
        paths:
            - '%kernel.project_dir%/design/tokens.json'

``color.primary`` and ``color.fg`` are aliases: they name a role and point to
a color of the palette. In ``css()``, a token is named after its path, without
the prefix of its category: ``color.blue.500`` is ``blue.500``,
``dimension.spacing.md`` is ``md``. See :doc:`tokens` for the details.

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
contains one rule per class:

.. code-block:: css

    @layer utilities {
        .p_md { padding: var(--dt-dimension-spacing-md); }
        .bdr_md { border-radius: var(--dt-dimension-radius-md); }
        .bg_gray\.50 { background: var(--dt-color-gray-50); }
        .c_fg { color: var(--dt-color-fg); }
        .c_primary { color: var(--dt-color-primary); }
    }

The rules read the CSS variables that the UX Design Tokens stylesheet declares.

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

Dark values belong to the token files: a Resolver document of UX Design Tokens
gives ``color.fg`` another value in a dark context. Its stylesheet then
redefines ``--dt-color-fg`` when the operating system prefers a dark scheme,
or when ``<html>`` has ``data-theme="dark"``:

.. code-block:: html+twig

    {# templates/base.html.twig #}
    <html lang="en" data-theme="dark">

Every ``color: 'fg'`` now uses the dark value, with no change to the
templates. :doc:`tokens` shows how ``_dark`` follows the same selectors.

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
