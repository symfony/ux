The cva() function
==================

``cva()`` describes the variants of a component with style hashes. It follows
the ``cva()`` function of Panda CSS and gives the same classes for the same
call. Like ``html_cva()`` of ``twig/html-extra``, it takes four named
arguments: ``base``, ``variants``, ``compoundVariants`` and
``defaultVariants``. Together, they are the config. Then call ``apply()`` on
the result with the variants you want:

.. code-block:: html+twig

    {# templates/components/Button.html.twig #}
    {% props size = 'sm', visual = 'solid' %}

    {% set button = cva(
        base: { px: '3', py: '1', rounded: 'md' },
        variants: {
            size: {
                sm: { fontSize: 'sm' },
                lg: { fontSize: 'lg', px: '5' },
            },
            visual: {
                solid: { bg: 'primary', color: 'fg' },
                outline: { borderWidth: '1px' },
            },
        },
        compoundVariants: [
            { size: 'lg', visual: 'outline', css: { borderWidth: '2px' } },
        ],
        defaultVariants: { size: 'sm', visual: 'solid' },
    ) %}

    <button {{ attributes.defaults({
        class: button.apply({ size: size, visual: visual }),
    }) }}>
        {% block content %}{% endblock %}
    </button>

``apply()`` returns a list of class names, like ``css()``. See
:doc:`css-function`.

In a sandboxed Twig template, the security policy must allow the ``cva``
function, the ``apply`` method of ``Symfony\UX\Css\Cva``, and the
``__toString`` method of ``Symfony\UX\Css\CssClassList``.

The hashes are merged before the classes are generated. For each property and
condition, the last value wins. With ``size: 'lg'`` above, ``px`` is ``5``, so
the result has ``px_5`` but not ``px_3``.

The style hashes are merged in this order:

#. ``base``;
#. the chosen variants: first the ones listed in ``defaultVariants``, in that
   order, then the other ones, in the order of the props given to
   ``apply()``;
#. the compound variants that match, in the order of the config.

When two variants set the same property, the one merged last wins.

Compound variants
-----------------

A compound variant adds styles when several variants are chosen together. Its
``css`` key holds the styles. Every other key is a variant name. The compound
variant matches when each of these variants has the given value:

.. code-block:: twig

    { size: 'lg', visual: 'outline', css: { borderWidth: '2px' } }

A key can also hold a list of values. The compound variant then matches when
the chosen value is one of them:

.. code-block:: twig

    { size: ['sm', 'lg'], visual: 'outline', css: { borderWidth: '2px' } }

Default variants
----------------

``defaultVariants`` gives the value used when ``apply()`` does not set a
variant. A variant set to ``null``, for example a component prop left empty,
uses the default too. Panda does this only for a variant that is not set.

A variant name or a value that is not declared is a Twig runtime error in
debug mode. Otherwise, it selects no styles for that variant. The default does
not apply in this case.

Variants chosen at runtime
--------------------------

``button.apply({ size: size })`` can use a value that is only known when the
template renders. This needs no ``static_css``. When Twig compiles the
template, the CSS of every hash of the config is written to the file, whether
or not the hash is used.

The config must be known at compile time
----------------------------------------

The config is read when Twig compiles the template. Write it in the call, or
use variables set once to a constant before the call, in the same body. The
rules are the same as in "Variables set in the template" in
:doc:`dynamic-styles`.

A config that Twig cannot read at compile time is a Twig syntax error. These
mistakes are Twig syntax errors too:

* an argument that is not ``base``, ``variants``, ``compoundVariants`` or
  ``defaultVariants``;
* a single hash with these keys, like ``cva({ base: {...} })``, instead of
  named arguments, like ``cva(base: {...})``;
* a style that is not valid, in any hash;
* a ``defaultVariants`` or compound variant key, or value, that is not
  declared in ``variants``.

Overriding the classes from the caller
--------------------------------------

The component above sets its classes as the default of its ``class``
attribute. So the caller can override them with ``css()``:

.. code-block:: html+twig

    <twig:Button size="lg" class="{{ css({ px: 'md' }) }}">Save</twig:Button>

The ``px`` of the caller replaces the ``px`` of the variant. The other classes
stay. This works like the override described in :doc:`css-function`, and needs
``symfony/ux-twig-component`` 3.8 or later.

Not supported yet
-----------------

Slot recipes (``sva()``), recipes from the configuration and patterns are not
supported. See :doc:`panda`.
