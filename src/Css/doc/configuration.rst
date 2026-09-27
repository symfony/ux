Configuration reference
=======================

Every option of the bundle, with its default value:

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        # "panda" adds the default tokens of Panda CSS under your tokens
        default_tokens: null

        # Raw values, grouped by category: colors, spacing, sizes, radii,
        # fontSizes, fontWeights, lineHeights, fonts, shadows, zIndex,
        # durations, easings
        tokens: {}

        # References to other tokens, like "{colors.blue.500}". A value with a
        # "base" key changes with the conditions it lists.
        semantic_tokens: {}

        # Added to the default conditions, or replacing the one with the same
        # name: a selector with "&", or an at-rule
        conditions: {}

        # Minimum widths, replacing the default breakpoints
        breakpoints: {}

        # Only accept tokens for properties bound to a token category; other
        # values must be written between brackets
        strict_tokens: true

        # Only accept the keywords of a property whose values are keywords
        strict_property_values: true

        # Values to write to the stylesheet even when no template uses them,
        # for css() calls with values only known at runtime
        static_css:
            css:
                -
                    # The values of each property, or "*" for every value it
                    # declares
                    properties: {}

                    # Conditions and breakpoints each value is also written for
                    conditions: []

                    # Also write each value for every breakpoint
                    responsive: false

``php bin/console config:dump-reference ux_css`` prints the same reference.

default_tokens
--------------

**type**: ``string`` **default**: ``null``

When set to ``panda``, this adds the default tokens of Panda CSS under the
project's tokens. See :doc:`tokens`.

tokens
------

**type**: ``array`` **default**: ``[]``

Raw token values, by category, then by name. Names can be nested in groups.
See :doc:`tokens`.

semantic_tokens
---------------

**type**: ``array`` **default**: ``[]``

Tokens that refer to other tokens with ``{category.path}``. A value with a
``base`` key is a conditional value. See :doc:`tokens`.

conditions
----------

**type**: ``array`` **default**: ``[]``

Conditions to add to the default ones of Panda CSS, or to replace them. The
name is written without the leading underscore. See :doc:`conditions`.

breakpoints
-----------

**type**: ``array`` **default**: ``[]``

Breakpoint names and their minimum widths. When set, the list replaces the
default breakpoints (``sm``, ``md``, ``lg``, ``xl``, ``2xl``). See
:doc:`conditions`.

strict_tokens
-------------

**type**: ``boolean`` **default**: ``true``

When enabled, a property bound to a token category only accepts the tokens of
that category, and other values must be written between square brackets. When
disabled, any string is accepted as is. This is the ``strictTokens`` option of
Panda CSS, which Panda disables by default.

strict_property_values
----------------------

**type**: ``boolean`` **default**: ``true``

When enabled, a property whose values are keywords, such as ``display`` or
``position``, only accepts those keywords. This is the
``strictPropertyValues`` option of Panda CSS, which Panda disables by default.

static_css
----------

**type**: ``array`` **default**: ``{ css: [] }``

Rules to write to the stylesheet whether or not a template uses them. Each
entry of ``css`` takes:

``properties``
    The values to write for each property. ``*`` stands for every value the
    property declares.

``conditions``
    Conditions (without the underscore) and breakpoints under which each value
    is also written.

``responsive``
    When ``true``, each value is also written for every breakpoint.

See :doc:`dynamic-styles`.
