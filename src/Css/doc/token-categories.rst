Token categories
================

Every token belongs to a category. The category decides which properties take
the token by name, and the shape of its value.

There are twenty-three categories, the same as Panda CSS, and they cannot be
changed. ``default_tokens: panda`` fills every category except ``zIndex``,
``borderWidths``, ``opacity``, ``cursor``, ``gradients``, ``assets`` and
``containerNames``, for which Panda CSS has no default token.

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

``letterSpacings``
------------------

Used by:

* ``letterSpacing``

The value is a length.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            letterSpacings: { tight: '-0.025em', wide: '0.05em' }

.. code-block:: html+twig

    <h1 class="{{ css({ letterSpacing: 'tight' }) }}">

.. code-block:: css

    --letter-spacings-tight: -0.025em;

``borders``
-----------

Used by:

* ``border``
* ``borderBlock`` (``borderY``)
* ``borderBlockEnd``
* ``borderBlockStart``
* ``borderBottom``
* ``borderInline`` (``borderX``)
* ``borderInlineEnd`` (``borderEnd``)
* ``borderInlineStart`` (``borderStart``)
* ``borderLeft``
* ``borderRight``
* ``borderTop``
* ``outline`` (``ring``)

The value is a border, or an object with a ``width``, a ``style`` and a
``color``. A number as ``width`` is in pixels, and the ``color`` can reference
a color token. The object goes under the ``value`` key.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            borders:
                subtle: '1px solid #e5e7eb'
                accent:
                    value: { width: 2, style: 'solid', color: '{colors.brand}' }

.. code-block:: html+twig

    <div class="{{ css({ border: 'accent' }) }}">

.. code-block:: css

    --borders-subtle: 1px solid #e5e7eb;
    --borders-accent: 2px solid var(--colors-brand);

``borderWidths``
----------------

Used by:

* ``borderBlockEndWidth``
* ``borderBlockStartWidth``
* ``borderBlockWidth`` (``borderYWidth``)
* ``borderBottomWidth``
* ``borderInlineEndWidth`` (``borderEndWidth``)
* ``borderInlineStartWidth`` (``borderStartWidth``)
* ``borderInlineWidth`` (``borderXWidth``)
* ``borderLeftWidth``
* ``borderRightWidth``
* ``borderTopWidth``
* ``borderWidth``
* ``divideX``
* ``divideY``
* ``focusRingWidth``
* ``outlineWidth`` (``ringWidth``)
* ``strokeWidth``

The value is a length.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            borderWidths: { thin: '1px', thick: '4px' }

.. code-block:: html+twig

    <div class="{{ css({ borderWidth: 'thick' }) }}">

``blurs``
---------

Used by:

* ``backdropBlur``
* ``blur``

The value is a length. The property writes it inside ``blur()``.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            blurs: { sm: '8px', md: '12px' }

.. code-block:: html+twig

    <div class="{{ css({ backdropFilter: 'auto', backdropBlur: 'md' }) }}">

.. code-block:: css

    --blurs-md: 12px;
    --backdrop-blur: blur(var(--blurs-md));

``opacity``
-----------

Used by:

* ``opacity``

The value is a number, such as ``0.6``, or a string.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            opacity: { muted: 0.6, disabled: 0.4 }

.. code-block:: html+twig

    <button class="{{ css({ opacity: 'disabled' }) }}">

``cursor``
----------

Used by:

* ``cursor``

The value is a cursor keyword.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            cursor: { action: 'pointer', busy: 'progress' }

.. code-block:: html+twig

    <button class="{{ css({ cursor: 'action' }) }}">

``aspectRatios``
----------------

Used by:

* ``aspectRatio``

The value is a ratio.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            aspectRatios: { video: '16 / 9', square: '1 / 1' }

.. code-block:: html+twig

    <iframe class="{{ css({ aspectRatio: 'video' }) }}">

.. code-block:: css

    --aspect-ratios-video: 16 / 9;

``gradients``
-------------

Used by:

* ``backgroundGradient`` (``bgGradient``)
* ``backgroundLinear`` (``bgLinear``)
* ``backgroundRadial`` (``bgRadial``)
* ``textGradient``

The value is a CSS gradient, or an object with a ``type`` (``linear`` or
``radial``), a ``placement`` and a list of ``stops``. A stop is a color, or all
the stops are objects with a ``color`` and a ``position`` in pixels. The object
goes under the ``value`` key.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            gradients:
                sunset:
                    value:
                        type: 'linear'
                        placement: 'to right'
                        stops: ['#f97316', '#db2777']
                spotlight: 'radial-gradient(circle, #fff, #000)'

.. code-block:: html+twig

    <section class="{{ css({ bgGradient: 'sunset' }) }}">

.. code-block:: css

    --gradients-sunset: linear-gradient(to right, #f97316, #db2777);

``bgGradient``, ``bgLinear`` and ``textGradient`` also take the directions of
Panda CSS, such as ``to-r``, with the ``gradientFrom``, ``gradientVia`` and
``gradientTo`` colors.

``assets``
----------

Used by:

* ``backgroundImage`` (``bgImage``)
* ``listStyleImage``

The value is a CSS image, or an object with a ``type`` and a ``value``. With
the ``url`` type, the value is a URL and the token is written as ``url()``.
With the ``svg`` type, the value is the markup of an SVG, which the token
writes as a data URI. The object goes under the ``value`` key.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            assets:
                logo:
                    value: { type: 'url', value: '/images/logo.svg' }
                dot:
                    value:
                        type: 'svg'
                        value: '<svg xmlns="http://www.w3.org/2000/svg">...</svg>'

.. code-block:: html+twig

    <header class="{{ css({ bgImage: 'logo' }) }}">

.. code-block:: css

    --assets-logo: url("/images/logo.svg");

``animations``
--------------

Used by:

* ``animation``

The value is an ``animation`` shorthand. UX CSS does not write keyframes: the
animation of a token must name ``@keyframes`` that your own CSS defines. That
includes the ``spin``, ``ping``, ``pulse`` and ``bounce`` animations of
``default_tokens: panda``.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            animations: { fade: 'fade-in 200ms ease-out' }

.. code-block:: html+twig

    <div class="{{ css({ animation: 'fade' }) }}">

``containerNames``
------------------

Used by:

* ``containerName``

The value is a container name, as ``@container`` queries refer to it.

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        tokens:
            containerNames: { sidebar: 'sidebar' }

.. code-block:: html+twig

    <aside class="{{ css({ containerName: 'sidebar' }) }}">
