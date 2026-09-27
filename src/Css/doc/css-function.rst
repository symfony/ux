The css() function
==================

``css()`` takes one hash of styles and returns a string of class names. Use it
wherever a class name goes:

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

A value cannot contain ``;``, ``{``, ``}``, a backslash, quotes, ``<`` or a
CSS comment. Neither can a selector or at-rule key. This stops a style from
writing extra CSS rules or breaking the ``class`` attribute.

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

The CSS layers
--------------

The generated file puts the variables of the breakpoints and the rules in two
cascade layers:

.. code-block:: css

    @layer tokens, utilities;

CSS written outside of any layer wins over layered CSS, whatever its
specificity. So a rule of your own stylesheet, or of a library such as
Bootstrap, overrides a ``css()`` class on the same property. To give
``css()`` the last word, put your own CSS in a layer declared before
``utilities``:

.. code-block:: css

    /* assets/styles/app.css */
    @layer base, tokens, utilities;

    @layer base {
        a { color: var(--dt-color-fg); }
    }

Layers are ordered by the first place their name appears. A later layer wins
over an earlier one. Put this statement at the top of the first stylesheet the
page loads, before the ``css()`` stylesheet. For ``!important`` declarations,
the order is reversed.

Output escaping
---------------

``css()`` output is escaped like any other string: a value with special
characters cannot break the HTML attribute.
