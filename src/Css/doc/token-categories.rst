Token categories
================

Every token belongs to a category. The category decides which properties take
the token by name, and the shape of its value.

There are twelve categories and they cannot be changed.
``default_tokens: panda`` fills every category except ``zIndex``.

A property listed under a category takes the tokens of that category, its own
keywords and numbers. With ``strict_tokens``, another value goes between square
brackets: see :doc:`tokens`. A property listed under no category takes its
keywords, numbers or any string: see :doc:`css-function`.

``colors``
----------

Used by:

* ``accentColor``
* ``background`` (``bg``)
* ``backgroundColor`` (``bgColor``)
* ``borderBlockColor`` (``borderYColor``)
* ``borderBlockEndColor``
* ``borderBlockStartColor``
* ``borderBottomColor``
* ``borderColor``
* ``borderInlineColor`` (``borderXColor``)
* ``borderInlineEndColor`` (``borderEndColor``)
* ``borderInlineStartColor`` (``borderStartColor``)
* ``borderLeftColor``
* ``borderRightColor``
* ``borderTopColor``
* ``boxShadowColor`` (``shadowColor``)
* ``caretColor``
* ``color``
* ``divideColor``
* ``fill``
* ``focusRingColor``
* ``gradientFrom``
* ``gradientTo``
* ``gradientVia``
* ``outlineColor`` (``ringColor``)
* ``scrollbarColor``
* ``stroke``
* ``textDecorationColor``
* ``textEmphasisColor``
* ``textShadowColor``
* ``WebkitTextFillColor``

The value is a color, or a reference to another color token. In ``css()``, a
color token followed by a slash and a number mixes the color with transparency,
using ``color-mix()``. ``bg: 'brand/50'`` keeps 50% of the color.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            colors:
                brand: '#4f46e5'
                gray: { 100: '#f3f4f6', 900: '#111827' }

.. code-block:: html+twig

    <p class="{{ css({ color: 'gray.900', bg: 'brand/50' }) }}">

``sizes``
---------

Used by:

* ``blockSize``
* ``boxSize``
* ``flexBasis``
* ``height`` (``h``)
* ``inlineSize``
* ``maxBlockSize``
* ``maxHeight`` (``maxH``)
* ``maxInlineSize``
* ``maxWidth`` (``maxW``)
* ``minBlockSize``
* ``minHeight`` (``minH``)
* ``minInlineSize``
* ``minWidth`` (``minW``)
* ``scrollbarWidth``
* ``width`` (``w``)

The value is a length. Each breakpoint also gives a size named ``breakpoint-``
followed by the breakpoint name, like ``breakpoint-md``.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            sizes: { prose: '65ch', container: '64rem' }

.. code-block:: html+twig

    <main class="{{ css({ maxW: 'container', w: 'breakpoint-md' }) }}">

``spacing``
-----------

Used by:

* ``borderSpacing``
* ``borderSpacingX``
* ``borderSpacingY``
* ``bottom``
* ``columnGap``
* ``focusRingOffset``
* ``gap``
* ``gridColumnGap``
* ``gridGap``
* ``gridRowGap``
* ``inset``
* ``insetBlock`` (``insetY``)
* ``insetBlockEnd``
* ``insetBlockStart``
* ``insetInline`` (``insetX``)
* ``insetInlineEnd`` (``end``, ``insetEnd``)
* ``insetInlineStart`` (``insetStart``, ``start``)
* ``left``
* ``margin`` (``m``)
* ``marginBlock`` (``marginY``, ``my``)
* ``marginBlockEnd``
* ``marginBlockStart``
* ``marginBottom`` (``mb``)
* ``marginInline`` (``marginX``, ``mx``)
* ``marginInlineEnd`` (``marginEnd``, ``me``)
* ``marginInlineStart`` (``marginStart``, ``ms``)
* ``marginLeft`` (``ml``)
* ``marginRight`` (``mr``)
* ``marginTop`` (``mt``)
* ``outlineOffset`` (``ringOffset``)
* ``padding`` (``p``)
* ``paddingBlock`` (``paddingY``, ``py``)
* ``paddingBlockEnd``
* ``paddingBlockStart``
* ``paddingBottom`` (``pb``)
* ``paddingInline`` (``paddingX``, ``px``)
* ``paddingInlineEnd`` (``paddingEnd``, ``pe``)
* ``paddingInlineStart`` (``paddingStart``, ``ps``)
* ``paddingLeft`` (``pl``)
* ``paddingRight`` (``pr``)
* ``paddingTop`` (``pt``)
* ``right``
* ``rowGap``
* ``scrollMargin``
* ``scrollMarginBlock`` (``scrollMarginY``)
* ``scrollMarginBlockEnd``
* ``scrollMarginBlockStart``
* ``scrollMarginBottom``
* ``scrollMarginInline`` (``scrollMarginX``)
* ``scrollMarginInlineEnd``
* ``scrollMarginInlineStart``
* ``scrollMarginLeft``
* ``scrollMarginRight``
* ``scrollMarginTop``
* ``scrollPadding``
* ``scrollPaddingBlock`` (``scrollPaddingY``)
* ``scrollPaddingBlockEnd``
* ``scrollPaddingBlockStart``
* ``scrollPaddingBottom``
* ``scrollPaddingInline`` (``scrollPaddingX``)
* ``scrollPaddingInlineEnd``
* ``scrollPaddingInlineStart``
* ``scrollPaddingLeft``
* ``scrollPaddingRight``
* ``scrollPaddingTop``
* ``scrollSnapMargin``
* ``scrollSnapMarginBottom``
* ``scrollSnapMarginLeft``
* ``scrollSnapMarginRight``
* ``scrollSnapMarginTop``
* ``spaceX``
* ``spaceY``
* ``textIndent``
* ``top``
* ``translateX`` (``x``)
* ``translateY`` (``y``)
* ``translateZ`` (``z``)

The value is a length. A dash before the name gives the negative value, like
``mt: '-sm'``.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            spacing: { sm: '0.5rem', md: '1rem', gutter: '2rem' }

.. code-block:: html+twig

    <div class="{{ css({ p: 'md', gap: 'gutter', mt: '-sm' }) }}">

``fonts``
---------

Used by:

* ``fontFamily``

The value is a font stack as a string, or a list of family names. A list is
joined with commas.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            fonts:
                body: ['Inter', 'sans-serif']
                mono: 'ui-monospace, monospace'

.. code-block:: html+twig

    <body class="{{ css({ fontFamily: 'body' }) }}">

.. code-block:: css

    --fonts-body: Inter, sans-serif;

``fontSizes``
-------------

Used by:

* ``fontSize``

The value is a length.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            fontSizes: { sm: '0.875rem', lg: '1.125rem' }

.. code-block:: html+twig

    <h2 class="{{ css({ fontSize: 'lg' }) }}">

``fontWeights``
---------------

Used by:

* ``fontWeight``

The value is a number or a keyword.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            fontWeights: { normal: 400, bold: 700 }

.. code-block:: html+twig

    <strong class="{{ css({ fontWeight: 'bold' }) }}">

``lineHeights``
---------------

Used by:

* ``lineHeight``

The value is a number or a length.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            lineHeights: { tight: 1.25, normal: 1.5 }

.. code-block:: html+twig

    <p class="{{ css({ lineHeight: 'tight' }) }}">

``radii``
---------

Used by:

* ``borderBottomLeftRadius`` (``roundedBottomLeft``)
* ``borderBottomRadius`` (``roundedBottom``)
* ``borderBottomRightRadius`` (``roundedBottomRight``)
* ``borderEndEndRadius`` (``roundedEndEnd``)
* ``borderEndRadius`` (``roundedEnd``)
* ``borderEndStartRadius`` (``roundedEndStart``)
* ``borderLeftRadius`` (``roundedLeft``)
* ``borderRadius`` (``rounded``)
* ``borderRightRadius`` (``roundedRight``)
* ``borderStartEndRadius`` (``roundedStartEnd``)
* ``borderStartRadius`` (``roundedStart``)
* ``borderStartStartRadius`` (``roundedStartStart``)
* ``borderTopLeftRadius`` (``roundedTopLeft``)
* ``borderTopRadius`` (``roundedTop``)
* ``borderTopRightRadius`` (``roundedTopRight``)

The value is a length.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            radii: { md: '0.375rem', full: '9999px' }

.. code-block:: html+twig

    <img class="{{ css({ rounded: 'full' }) }}">

``shadows``
-----------

Used by:

* ``boxShadow`` (``shadow``)
* ``textShadow``

The value is a shadow, or a list of shadows. A list is joined with commas to
layer the shadows. A shadow can reference a color token.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            shadows:
                card: ['0 1px 2px rgb(0 0 0 / 0.05)', '0 1px 3px rgb(0 0 0 / 0.1)']
                focus: '0 0 0 3px {colors.brand}'

.. code-block:: html+twig

    <div class="{{ css({ shadow: 'card' }) }}">

.. code-block:: css

    --shadows-card: 0 1px 2px rgb(0 0 0 / 0.05), 0 1px 3px rgb(0 0 0 / 0.1);
    --shadows-focus: 0 0 0 3px var(--colors-brand);

``easings``
-----------

Used by:

* ``animationTimingFunction``
* ``transitionTimingFunction``

The value is a timing function, or the four numbers of a ``cubic-bezier()`` as
a list.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            easings:
                out: [0, 0, 0.2, 1]
                linear: 'linear'

.. code-block:: html+twig

    <a class="{{ css({ transitionTimingFunction: 'out' }) }}">

.. code-block:: css

    --easings-out: cubic-bezier(0, 0, 0.2, 1);

``durations``
-------------

Used by:

* ``animationDelay``
* ``animationDuration``
* ``transitionDelay``
* ``transitionDuration``

The value is a time.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            durations: { fast: '150ms', slow: '300ms' }

.. code-block:: html+twig

    <a class="{{ css({ transitionDuration: 'fast' }) }}">

``zIndex``
----------

Used by:

* ``zIndex``

The value is a number.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            zIndex: { dropdown: 1000, modal: 1400 }

.. code-block:: html+twig

    <dialog class="{{ css({ zIndex: 'modal' }) }}">
