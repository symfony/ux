Dynamic styles
==============

Most styles are written in the template, and the bundle resolves them when
Twig compiles it. Some values are only known when the template renders: a
status that picks a color, a size passed to a component. ``css()`` accepts
them too.

Ternaries
---------

A ternary whose two branches are written in the template is still resolved
when Twig compiles it. This works whether the branches are values or whole
hashes:

.. code-block:: html+twig

    <button class="{{ css({ p: 'md', color: isActive ? 'primary' : 'fg' }) }}">

    <button class="{{ css(isActive ? { color: 'primary' } : { p: 'lg' }) }}">

Both branches are checked, both end up in the CSS file, and rendering only
picks one of the two class strings.

Variables set in the template
-----------------------------

A variable set once to a constant with ``{% set %}`` is resolved when Twig
compiles the template, like a value written in the call:

.. code-block:: html+twig

    {% set tone = 'primary' %}
    <button class="{{ css({ color: tone }) }}">

This works when the ``set`` comes before the call and in the same body: the
top level of the template, one block, or one macro. The ``set`` must not be
inside an ``if`` or a ``for``, and the call must not be inside a ``with``. A
variable assigned more than once, or set anywhere else, is resolved when the
template renders, as below.

Values from variables
---------------------

A value, or the whole hash, can come from a variable:

.. code-block:: html+twig

    {# a value #}
    <span class="{{ css({ p: 'sm', color: status.color }) }}">

    {# the whole hash #}
    <div class="{{ css(styles) }}">

The styles under a condition or a breakpoint can come from a variable too.
So can the hashes a spread adds, like ``{ ...base, p: 'md' }``.

The parts written in the template are resolved when it compiles, as usual.
The rest is resolved when the template renders: ``css()`` computes the class
names from tables prepared when the container is built, in a few microseconds
per call.

The CSS of these classes must exist. The bundle writes the CSS of the styles
it finds in the templates, but it cannot guess the values of a variable. A
class computed at runtime has CSS only if the same style is written somewhere
in a template, or if ``static_css`` lists it.

Listing values with static_css
------------------------------

``static_css`` writes rules to the CSS file whether or not a template uses
them. It follows the ``staticCss`` option of Panda CSS:

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        static_css:
            css:
                # c_success, c_warning, c_danger, and their hover variants
                - properties:
                      color: [success, warning, danger]
                  conditions: [hover]

                # p_sm, p_md, p_lg... and the same at every breakpoint
                - properties:
                      padding: ['*']
                  responsive: true

Each rule lists properties and their values. ``*`` stands for every value the
property declares: the tokens of its category, or the values Panda CSS lists
for it. ``conditions`` adds the same values under conditions or
breakpoints, and ``responsive: true`` adds them at every breakpoint.

``static_css`` is checked like a template when the container is built: an
unknown property or token is a configuration error.

``*`` on a large category writes many rules. List the values you need when
you know them.

Finding missing CSS
-------------------

In debug mode, ``css()`` logs a warning on the ``ux_css`` channel for every
class computed at runtime that has no CSS:

.. code-block:: text

    No CSS was generated for "c_teal.300"; use it in a template or cover it
    with ux_css.static_css.

The warnings show up in the profiler's Logs panel. Add the values to
``static_css``, or write the style in a template, and the warning goes away.

Values from users
-----------------

``css()`` output is escaped like any other string. So a value cannot break the
``class`` attribute. A value that is not a token, such as text typed by a user,
is refused with a Twig runtime error in debug mode. Otherwise, it gives a class
that has no CSS. Map user input to a known list of values before passing it to
``css()``.
