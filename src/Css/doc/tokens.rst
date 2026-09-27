Design tokens
=============

Tokens are the named values your styles use: colors, spacing, font sizes and so
on. UX CSS reads them from `UX Design Tokens`_, which loads token files written
in the `DTCG`_ format. ``css()`` only accepts their names, and the generated
rules use the CSS variables UX Design Tokens writes.

Where tokens come from
----------------------

A token file is JSON. A node with ``$value`` is a token, and its path is its
position in the tree:

.. code-block:: json

    {
        "color": {
            "$type": "color",
            "palette": {
                "blue-500": { "$value": { "colorSpace": "srgb", "components": [0.23, 0.51, 0.96] } }
            },
            "action": {
                "primary": { "$value": "{color.palette.blue-500}" }
            }
        },
        "dimension": {
            "$type": "dimension",
            "spacing": {
                "md": { "$value": { "value": 1, "unit": "rem" } }
            },
            "radius": {
                "control": { "$value": { "value": 0.375, "unit": "rem" } }
            }
        }
    }

UX Design Tokens loads the file:

.. code-block:: yaml

    # config/packages/ux_design_tokens.yaml
    ux_design_tokens:
        paths:
            - '%kernel.project_dir%/design/tokens.json'

UX CSS reads the tokens when the container is built. Editing a token file
rebuilds the container in debug mode, including the files that a Resolver
document or a ``$ref`` points to. The paths must be known at that moment, so a
token file cannot come from an environment variable.

Names in css()
--------------

The path of a token gives its category and its name. The first segments must
match one of the category's prefixes, and the rest of the path is the name:

=============== ========================================================= ======================
Category        Prefixes                                                  Token types
=============== ========================================================= ======================
``colors``      ``color``, ``colors``                                     ``color``
``spacing``     ``dimension.spacing``, ``dimension.space``, ``spacing``,  ``dimension``
                ``space``
``sizes``       ``dimension.size``, ``dimension.sizes``, ``size``,        ``dimension``
                ``sizes``
``radii``       ``dimension.radius``, ``dimension.radii``, ``radius``,    ``dimension``
                ``radii``, ``rounded``
``fontSizes``   ``font.size``, ``font-size``, ``fontSize``, ``fontSizes`` ``dimension``
``fontWeights`` ``font.weight``, ``font-weight``, ``fontWeight``,         ``fontWeight``,
                ``fontWeights``                                           ``number``
``lineHeights`` ``font.line-height``, ``line.height``, ``line-height``,   ``number``,
                ``lineHeight``, ``lineHeights``, ``leading``              ``dimension``
``fonts``       ``font.family``, ``font-family``, ``fontFamily``,         ``fontFamily``
                ``fontFamilies``, ``fonts``
``shadows``     ``shadow``, ``shadows``, ``elevation``                    ``shadow``
``zIndex``      ``z-index``, ``zIndex``                                   ``number``
``durations``   ``duration``, ``durations``, ``motion.duration``          ``duration``
``easings``     ``easing``, ``easings``, ``motion.easing``                ``cubicBezier``
``breakpoints`` ``breakpoint``, ``breakpoints``, ``screens``              ``dimension``
=============== ========================================================= ======================

With the file above, ``color.action.primary`` is the ``action.primary`` color,
``color.palette.blue-500`` is ``palette.blue-500``, ``dimension.spacing.md`` is
the ``md`` spacing, and ``dimension.radius.control`` is the ``control``
radius:

.. code-block:: html+twig

    <button class="{{ css({ color: 'action.primary', p: 'md', rounded: 'control' }) }}">

A few rules complete the table:

* when several prefixes match, the longest one wins;
* a token named ``$root`` takes the name of its group;
* a token is left out if the category does not accept its type, like a
  ``number`` token under ``color``;
* a token outside every prefix is left out: ``css()`` does not know it;
* two paths that give the same name, like ``color.red`` and ``colors.red``, are
  an error.

Which properties take which tokens
----------------------------------

Each property takes the tokens of one category. The mapping comes from Panda
CSS and cannot be changed. The most common ones:

=============== ==============================================================
Category        Properties (and their shorthands)
=============== ==============================================================
``colors``      ``color``, ``background`` (``bg``), ``backgroundColor``
                (``bgColor``), ``borderColor``, ``outlineColor``, ``fill``,
                ``stroke``, ``accentColor``, ``caretColor``
``spacing``     ``padding`` (``p``, ``px``, ``py``, ``pt``, ...), ``margin``
                (``m``, ``mx``, ``my``, ``mt``, ...), ``gap``, ``rowGap``,
                ``columnGap``, ``top``, ``right``, ``bottom``, ``left``,
                ``inset``
``sizes``       ``width`` (``w``), ``height`` (``h``), ``minWidth``
                (``minW``), ``maxWidth`` (``maxW``), ``minHeight``
                (``minH``), ``maxHeight`` (``maxH``)
``radii``       ``borderRadius`` (``rounded``) and its sides and corners
``fontSizes``   ``fontSize``
``fontWeights`` ``fontWeight``
``lineHeights`` ``lineHeight``
``fonts``       ``fontFamily``
``shadows``     ``boxShadow`` (``shadow``), ``textShadow``
``zIndex``      ``zIndex``
``durations``   ``transitionDuration``, ``transitionDelay``,
                ``animationDuration``, ``animationDelay``
``easings``     ``transitionTimingFunction``, ``animationTimingFunction``
=============== ==============================================================

CSS variables
-------------

The rules read the variables of UX Design Tokens:

.. code-block:: css

    .c_action\.primary { color: var(--dt-color-action-primary); }
    .p_md { padding: var(--dt-dimension-spacing-md); }

UX CSS does not declare these variables: UX Design Tokens does. Add its
stylesheet to the base template, next to the stylesheet of UX CSS:

.. code-block:: html+twig

    {# templates/base.html.twig #}
    {% block stylesheets %}
        {{ ux_token_stylesheet() }}
        <link rel="stylesheet" href="{{ asset('ux_css/styles.css') }}">
    {% endblock %}

``ux_token_css()`` inlines the same variables in a ``<style>`` element instead.
The ``css_prefix`` option of UX Design Tokens renames the variables, and the
rules follow it.

Hand-written CSS can use the same variables, so it matches the tokens:

.. code-block:: css

    /* assets/styles/app.css */
    .prose a {
        color: var(--dt-color-action-primary);
    }

Light, dark and themes
----------------------

Dark values come from UX Design Tokens: a Resolver document gives the tokens
another value in a dark context, and its stylesheet redefines the variables
for dark mode. ``color: 'action.primary'`` follows dark mode by itself, with
no change to the templates.

For styles that only apply in dark mode, ``_dark`` uses the selectors of UX
Design Tokens, so it applies exactly when the dark variables do:

.. code-block:: css

    :root[data-theme="dark"] .dark\:bg_palette\.slate-900 { ... }

    @media (prefers-color-scheme: dark) {
        :root:not([data-theme="light"]) .dark\:bg_palette\.slate-900 { ... }
    }

A theme per user, brand or tenant uses a Resolver input: ``ux_token_css()`` and
``ux_token_stylesheet()`` write other values for the same variables, and the
classes of ``css()`` stay the same. UX CSS reads every context of the Resolver
document, so a token defined in only one context is accepted everywhere.

Breakpoints
-----------

``dimension`` tokens under ``breakpoint``, ``breakpoints`` or ``screens``
replace the breakpoints of Panda CSS:

.. code-block:: json

    {
        "breakpoint": {
            "$type": "dimension",
            "tablet": { "$value": { "value": 48, "unit": "rem" } }
        }
    }

``css({ tablet: { p: 'lg' } })`` then writes its rule under
``@media screen and (min-width: 48rem)``. Without breakpoint tokens, the
breakpoints of Panda CSS apply: ``sm``, ``md``, ``lg``, ``xl`` and ``2xl``.

A media query cannot change per request, so a breakpoint must have the same
value in every context of the Resolver document.

Values that are not tokens
--------------------------

``strict_tokens`` is enabled by default: a property bound to a category only
accepts the tokens of that category. To use another value, write it between
square brackets:

.. code-block:: html+twig

    <div class="{{ css({ w: '[37ch]', color: '[#ff6600]' }) }}">

The brackets give the classes ``w_[37ch]`` and ``c_[#ff6600]``. The value is
written to the CSS as is. With ``strict_tokens: false``, any string is accepted
without brackets, as in Panda CSS by default.

With ``strict_tokens``, a category that has no token at all refuses bare
strings too. The error lists the paths to fill:

.. code-block:: text

    Unknown fontSizes token "lg": no fontSizes token is declared. Add fontSizes
    tokens to the design tokens, under font.size, font-size, fontSize or
    fontSizes, or write a raw value between brackets, like "[lg]".

Numbers and keywords such as ``bold`` are still accepted.

A few more forms are accepted wherever a token is expected:

* ``bg: 'action.primary/50'`` mixes the color with 50% transparency, using
  ``color-mix()``;
* ``mt: '-md'`` gives the negative value of a spacing token;
* ``gap: 'var(--card-gap)'`` uses your own CSS variable.

Composite tokens (``typography``, ``border``, ``transition``, ``gradient`` and
``strokeStyle``) are not available in ``css()``.

Configuration errors
--------------------

The tokens are checked when the container is built, so ``cache:clear``
reports mistakes before any template is rendered:

=========================================== =====================================
Mistake                                     Message
=========================================== =====================================
Two paths for one name                      ``The "color.red" and "colors.red"
                                            design tokens both give the "red"
                                            colors token.``
Breakpoint that changes with the context    ``The "breakpoint.md" design token
                                            must have the same value in every
                                            Resolver context, because a media
                                            query cannot change per request.``
Token file from an environment variable     ``UX CSS reads the design tokens
                                            when the container compiles, so the
                                            "%env(TOKENS_FILE)%" design token
                                            path cannot come from an
                                            environment variable.``
=========================================== =====================================

Errors in the token files themselves, such as an alias to a token that does not
exist, come from UX Design Tokens, and include the path of the token.

.. _`UX Design Tokens`: https://symfony.com/bundles/ux-design-tokens/current/index.html
.. _`DTCG`: https://www.designtokens.org/tr/2025.10/
