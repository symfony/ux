Design tokens
=============

Tokens are the named values your styles use: colors, spacing, font sizes and so
on. You declare them once in the bundle configuration. ``css()`` only accepts
their names. Each one becomes a CSS variable.

Raw tokens
----------

``tokens`` holds raw values, grouped by category:

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            colors:
                blue: { 500: '#3b82f6', 600: '#2563eb' }
                gray: { 50: '#f9fafb', 900: '#111827' }
                white: '#ffffff'
            spacing: { sm: '0.5rem', md: '1rem', lg: '2rem' }
            sizes: { prose: '65ch' }
            radii: { md: '0.375rem', full: '9999px' }
            fontSizes: { sm: '0.875rem', lg: '1.125rem' }
            fonts:
                body: ['Inter', 'sans-serif']

The categories are fixed: ``colors``, ``spacing``, ``sizes``, ``radii``,
``fontSizes``, ``fontWeights``, ``lineHeights``, ``fonts``, ``shadows``,
``zIndex``, ``durations`` and ``easings``. Inside a category, tokens can be
nested in groups: ``blue.500`` is the ``500`` token of the ``blue`` group. A
``fonts``, ``shadows`` or ``easings`` token can be a list, which is joined with
commas.

In ``css()``, a token is its name as a string:

.. code-block:: html+twig

    <p class="{{ css({ color: 'blue.500', p: 'md', maxW: 'prose', fontFamily: 'body' }) }}">

Default tokens
--------------

``default_tokens: panda`` adds the default tokens of Panda CSS: a palette of
colors from ``red.50`` to ``red.950`` and more, a spacing scale from ``0`` to
``96``, and sizes, radii, font sizes, font weights, line heights, fonts,
shadows, durations and easings. The Symfony Flex recipe enables it.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        default_tokens: panda
        tokens:
            colors:
                brand: '#4f46e5'
                red: { 500: '#e11d48' }

Your tokens are added to the default ones. A token with the same path replaces
the default one: here, ``red.500`` changes, and the other ``red`` shades stay.

Semantic tokens
---------------

``semantic_tokens`` gives names to other tokens, by reference:

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        semantic_tokens:
            colors:
                primary: '{colors.blue.500}'
                fg: { base: '{colors.gray.900}', _dark: '{colors.gray.50}' }
                bg: { base: '{colors.white}', _dark: '{colors.gray.900}' }

A reference is written ``{category.path}``. It can point to a raw token or to
another semantic token.

A value with a ``base`` key is a conditional value. ``base`` applies by
default. Each other key is a condition, such as ``_dark``, that applies another
value. Templates use the semantic name and never repeat the condition:
``color: 'fg'`` follows dark mode by itself. :doc:`conditions` lists the
available conditions.

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

Tokens as CSS variables
-----------------------

Every token becomes a CSS variable in the ``tokens`` layer of the generated
file, whether a template uses it or not. The name is the category followed by
the path, with dashes:

.. code-block:: css

    @layer tokens {
        :where(:root, :host) {
            --colors-blue-500: #3b82f6;
            --spacing-md: 1rem;
            --fonts-body: Inter, sans-serif;
            --colors-primary: var(--colors-blue-500);
            --colors-fg: var(--colors-gray-900);
        }

        .dark {
            --colors-fg: var(--colors-gray-50);
        }
    }

A semantic token refers to the variable of its target. A conditional value is
written under the selector of its condition. The breakpoints are written as
variables too (``--breakpoints-md``).

Hand-written CSS can use the same variables, so it matches the tokens:

.. code-block:: css

    /* assets/styles/app.css */
    .prose a {
        color: var(--colors-primary);
        text-underline-offset: var(--spacing-sm);
    }

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
strings too. The error says which key to fill:

.. code-block:: text

    Unknown fontSizes token "lg": no fontSizes token is declared. Add them under
    ux_css.tokens.fontSizes, or write a raw value between brackets, like "[lg]".

Numbers and keywords such as ``bold`` are still accepted.

A few more forms are accepted wherever a token is expected:

* ``bg: 'primary/50'`` mixes the color with 50% transparency, using
  ``color-mix()``;
* ``mt: '-md'`` gives the negative value of a spacing token;
* ``gap: 'var(--card-gap)'`` uses a CSS variable of your own.

Configuration errors
--------------------

The token configuration is checked when the container is built, so
``cache:clear`` reports mistakes before any template is rendered:

=========================================== =====================================
Mistake                                     Message
=========================================== =====================================
Unknown category                            ``Unknown token category "color".
                                            Did you mean "colors"?``
Reference to a token that does not exist    ``The "colors.primary" token
                                            references the unknown token
                                            "colors.rde". Did you mean
                                            "colors.red"?``
Tokens that refer to each other             ``Circular token reference:
                                            colors.a -> colors.b -> colors.a.``
Unknown condition in a conditional value    ``The "colors.fg" token uses the
                                            unknown condition "_drak". Did you
                                            mean "_dark"?``
=========================================== =====================================
