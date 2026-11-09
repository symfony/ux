The css() function
==================

``css()`` takes one hash of styles. It returns a list of class names
(``Symfony\UX\Css\CssClassList``) that prints as a string. Use it wherever a
class name goes:

.. code-block:: html+twig

    <div class="{{ css({ display: 'flex', gap: 'md', p: 'lg' }) }}">

    {# with other classes #}
    <div class="card {{ css({ p: 'lg' }) }}">

The syntax follows the ``css()`` function of Panda CSS, and so do the class
names and the CSS.

Properties
----------

Properties are CSS properties written in camelCase: ``backgroundColor``,
``borderTopWidth``, ``gridTemplateColumns``. Panda CSS shorthands are accepted
too, and give the same classes as the full name:

=================================== ==========================================
Shorthand                           Property
=================================== ==========================================
``p``, ``px``, ``py``               ``padding``, ``paddingInline``,
                                    ``paddingBlock``
``pt``, ``pr``, ``pb``, ``pl``      ``paddingTop``, ``paddingRight``,
                                    ``paddingBottom``, ``paddingLeft``
``m``, ``mx``, ``my``               ``margin``, ``marginInline``,
                                    ``marginBlock``
``mt``, ``mr``, ``mb``, ``ml``      ``marginTop``, ``marginRight``,
                                    ``marginBottom``, ``marginLeft``
``w``, ``h``                        ``width``, ``height``
``minW``, ``maxW``                  ``minWidth``, ``maxWidth``
``minH``, ``maxH``                  ``minHeight``, ``maxHeight``
``bg``, ``bgColor``                 ``background``, ``backgroundColor``
``rounded``                         ``borderRadius``
``shadow``                          ``boxShadow``
=================================== ==========================================

A CSS variable can be set as a property too: ``'--card-gap': '[1rem]'``.

Values
------

What a property accepts depends on the property:

* a property bound to a token category takes a token name, like
  ``color: 'primary'`` or ``p: 'md'``. See :doc:`tokens`;
* a property whose values are keywords takes one of them:
  ``display: 'flex'``, ``position: 'sticky'``, ``textAlign: 'center'``;
* a property whose values are numbers takes a number: ``zIndex: 10``,
  ``opacity: 0.5``, ``flexGrow: 1``;
* a property with a more complex syntax takes any string:
  ``gridTemplateColumns: 'repeat(3, 1fr)'``.

Every property also accepts the CSS-wide keywords ``inherit``, ``initial``,
``unset``, ``revert`` and ``revert-layer``. It also accepts a value between
square brackets (``w: '[37ch]'``) and a CSS variable (``gap:
'var(--card-gap)'``).

A value that ends with ``!`` or ``!important`` is written with
``!important``: ``color: 'primary!'``.

A value can contain CSS strings, as long as every quote opens and closes a
string. This is how you write a pseudo-element or grid areas:

.. code-block:: html+twig

    <blockquote class="{{ css({ _before: { content: '""' } }) }}">

    {{ css({ gridTemplateAreas: '"header header" "nav main"' }) }}

A value cannot contain ``;``, ``{``, ``}``, a backslash, ``<``, a CSS comment, a
line break inside a string or a quote that is not part of a complete string,
and this applies inside strings too. Write the character itself instead of an
escape sequence: ``content: '"→"'``, not ``content: '"\2192"'``. A selector or
at-rule key cannot contain quotes at all. This stops a style from writing extra
CSS rules or breaking the ``class`` attribute.

Conditions and breakpoints
--------------------------

A key that starts with an underscore is a condition. A breakpoint name is a
breakpoint. Both hold a hash of styles, and both can be used inside a value
too. See :doc:`conditions`.

Selectors and at-rules
----------------------

A key that starts or ends with ``&``, or starts with ``@``, is a selector or
an at-rule of its own:

.. code-block:: html+twig

    <ul class="{{ css({ '& > li': { mt: 'sm' }, '@media print': { display: 'none' } }) }}">

``&`` stands for the element that has the class. Prefer a named condition when
one exists: ``_hover`` rather than ``'&:hover'``.

Class names
-----------

You never write the class names, but you see them in the HTML. A class name is
made of the conditions, a short name for the property, and the value:

====================================== =======================================
Style                                  Class name
====================================== =======================================
``p: 'md'``                            ``p_md``
``padding: 'md'``                      ``p_md``
``color: 'blue.500'``                  ``c_blue.500``
``_hover: { color: 'primary' }``       ``hover:c_primary``
``md: { p: 'lg' }``                    ``md:p_lg``
``w: '[37ch]'``                        ``w_[37ch]``
====================================== =======================================

The same style always gives the same class, in every template, so the CSS
file holds each rule once.

Overriding the classes of a component
-------------------------------------

A Twig component can let its caller override some of its styles. For this, the
component sets its classes with ``css()`` as the default of its ``class``
attribute:

.. code-block:: html+twig

    {# templates/components/Button.html.twig #}
    <button {{ attributes.defaults({ class: css({ px: '3', color: 'primary' }) }) }}>
        {% block content %}{% endblock %}
    </button>

The caller passes its own ``css()`` result as the ``class`` of the component:

.. code-block:: html+twig

    <twig:Button class="{{ css({ px: '5' }) }}">Save</twig:Button>

    {# renders <button class="c_primary px_5"> #}

The ``px`` of the caller replaces the ``px`` of the component. Two classes
match when they have the same conditions, in the same order, and the same CSS
property, after resolving shorthands. So ``hover:p_lg`` replaces ``hover:p_md``,
but ``p_lg`` does not. ``md:hover:p_lg`` does not replace ``hover:md:p_md``
either, because the conditions are not in the same order. ``p`` (``padding``)
does not replace ``px`` (``paddingInline``), so both classes stay.

Only the result of ``css()`` is merged. A plain string is never parsed or
deduplicated. ``class="px_5"`` and ``class="card {{ css({ px: '5' }) }}"`` are
appended to the classes of the component, and the stylesheet decides which rule
wins.

.. note::

    The override needs ``symfony/ux-twig-component`` 3.8 or later. With an
    older version, the component turns the ``css()`` value of the caller into a
    string, and the classes of both sides are present.

.. warning::

    When a LiveComponent is rendered again, it gets the ``class`` of its caller
    back as a string, not as a list of classes. The classes are then appended
    instead of merged: ``px_3`` and ``px_5`` are present together again.

``css()`` no longer returns a string. ``html_classes()`` refuses its result,
and the ``variants`` of ``html_cva()`` silently ignore it. Append an empty
string to get the string: ``css({ px: '5' }) ~ ''``.

The CSS layers
--------------

The generated file puts the tokens and the rules in two cascade layers:

.. code-block:: css

    @layer tokens, utilities;

With the :doc:`preflight <preflight>` option, it also writes a CSS reset in a
``reset`` layer, which comes first:

.. code-block:: css

    @layer reset, tokens, utilities;

CSS written outside of any layer wins over layered CSS, whatever its
specificity. So a rule of your own stylesheet, or of a library such as
Bootstrap, overrides a ``css()`` class on the same property. To give
``css()`` the last word, put your own CSS in layers declared around the ones of
the bundle. This follows the layer order of Panda CSS, with ``components``
instead of ``recipes`` for the CSS of your own components:

.. code-block:: css

    /* assets/styles/app.css */
    @layer reset, base, tokens, components, utilities;

    @layer base {
        a { color: var(--colors-fg); }
    }

Layers are ordered by the first place their name appears. A later layer wins
over an earlier one. Put this statement at the top of the first stylesheet the
page loads, before the ``css()`` stylesheet. The statement can list the
``reset`` layer even when ``preflight`` is off: an unused layer has no effect.
For ``!important`` declarations, the order is reversed.

Output escaping
---------------

``css()`` output is escaped like any other string: a value with special
characters cannot break the HTML attribute.
