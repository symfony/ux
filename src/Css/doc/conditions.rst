Conditions and breakpoints
==========================

A condition applies styles in a given state: on hover, in dark mode, when an
input is invalid... A breakpoint applies them from a given screen width. Both
come from Panda CSS, with the same names and the same selectors.

Writing a condition
-------------------

In ``css()``, a condition starts with an underscore. There are two ways to
write it. Both give the same classes:

.. code-block:: html+twig

    {# a hash of styles under the condition #}
    <a class="{{ css({ color: 'fg', _hover: { color: 'primary', bg: 'gray.50' } }) }}">

    {# a conditional value, with "base" for the default #}
    <a class="{{ css({ color: { base: 'fg', _hover: 'primary' } }) }}">

The first form groups several properties under one condition. The second one
keeps all the values of one property together. ``base`` is only valid inside a
conditional value.

Conditions can be nested. The class names show the chain:

.. code-block:: html+twig

    <button class="{{ css({ _hover: { _dark: { bg: 'gray.900' } } }) }}">

This gives ``hover:dark:bg_gray.900``, which applies on hover in dark mode.

Default conditions
------------------

The bundle ships every condition of Panda CSS. The most common ones:

======================= ======================================================
Condition               Applies when
======================= ======================================================
``_hover``              ``&:is(:hover, [data-hover])``
``_focus``              ``&:is(:focus, [data-focus])``
``_focusVisible``       ``&:is(:focus-visible, [data-focus-visible])``
``_active``             ``&:is(:active, [data-active])``
``_disabled``           ``&:is(:disabled, [disabled], [data-disabled],
                        [aria-disabled=true])``
``_checked``            ``&:is(:checked, [data-checked], [aria-checked=true],
                        [data-state="checked"])``
``_invalid``            ``&:is(:invalid, [data-invalid], [aria-invalid=true])``
``_expanded``           ``&:is([aria-expanded=true], [data-expanded],
                        [data-state="expanded"])``
``_open``               ``&:is([open], [data-open], [data-state="open"],
                        :popover-open)``
``_placeholder``        ``&::placeholder, &[data-placeholder]``
``_before``, ``_after`` ``&::before``, ``&::after``
``_first``, ``_last``   ``&:first-child``, ``&:last-child``
``_even``, ``_odd``     ``&:nth-child(even)``, ``&:nth-child(odd)``
``_dark``, ``_light``   ``.dark &``, ``.light &``
``_osDark``             ``@media (prefers-color-scheme: dark)``
``_motionReduce``       ``@media (prefers-reduced-motion: reduce)``
``_print``              ``@media print``
``_groupHover``         ``.group:is(:hover, [data-hover]) &``
``_peerFocus``          ``.peer:is(:focus, [data-focus]) ~ &``
======================= ======================================================

``&`` stands for the element that has the class. The full list is in the
`Panda CSS documentation`_.

``_groupHover`` and the other ``_group*`` conditions style an element when an
ancestor with the ``group`` class is hovered, focused, and so on. The
``_peer*`` conditions do the same with a previous sibling that has the
``peer`` class:

.. code-block:: html+twig

    <a href="#" class="group">
        <span class="{{ css({ color: 'fg', _groupHover: { color: 'primary' } }) }}">Docs</span>
    </a>

Dark mode
---------

By default, ``_dark`` applies inside an element that has the ``dark`` class,
usually ``<html>``. To follow the operating system preference instead, use
``_osDark``. To use another selector, redefine the condition:

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        conditions:
            dark: '[data-theme=dark] &'

Semantic tokens follow the new selector too: their dark values are written
under ``[data-theme=dark]``.

Your own conditions
-------------------

``conditions`` adds conditions, or replaces a default one with the same name.
A condition is a selector that contains ``&``, or an at-rule:

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        conditions:
            expanded: '&[aria-expanded=true]'
            sidebarOpen: 'body.sidebar-open &'
            supportsGrid: '@supports (display: grid)'

Declare the name without the underscore, and use it with one:
``_sidebarOpen: { ml: 'lg' }``.

A condition without ``&`` that does not start with ``@`` is refused when the
container is built:

.. code-block:: text

    The "dark" condition must contain "&" or start with "@", "[data-theme=dark]"
    given.

Breakpoints
-----------

Breakpoints are written without an underscore. They are mobile-first. A
breakpoint applies from its minimum width. ``base`` applies below the first
one.

.. code-block:: html+twig

    {# a hash of styles under the breakpoint #}
    <div class="{{ css({ p: 'sm', md: { p: 'lg' } }) }}">

    {# a conditional value #}
    <div class="{{ css({ p: { base: 'sm', md: 'lg' } }) }}">

Both give ``p_sm md:p_lg``. The default breakpoints are those of Panda CSS:

========= =================
Name      Minimum width
========= =================
``sm``    ``640px``
``md``    ``768px``
``lg``    ``1024px``
``xl``    ``1280px``
``2xl``   ``1536px``
========= =================

The media queries are written in ``rem``: ``md`` gives
``@media screen and (min-width: 48rem)``.

Each breakpoint also comes with ranges: ``mdOnly`` applies from ``md`` up to
``lg``, ``mdDown`` below ``md``, and ``smToLg`` from ``sm`` up to ``lg``.

An array is a short form for the breakpoints in order, starting at ``base``:
``p: ['sm', 'md', 'lg']`` means ``{ base: 'sm', sm: 'md', md: 'lg' }``.

``breakpoints`` replaces the default list:

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        breakpoints:
            tablet: '48rem'
            desktop: '80rem'

A breakpoint cannot have the name of a CSS property or shorthand, since both
are written as keys of the same hash: ``p`` or ``color`` is refused when the
container is built.

.. _`Panda CSS documentation`: https://panda-css.com/docs/concepts/conditional-styles
