Panda CSS
=========

UX CSS brings the ``css()`` function of `Panda CSS`_ to Twig. For the same
configuration, it gives the same class names and the same CSS as Panda. It
does not run Panda: the rules are written in PHP.

How the output is kept identical
--------------------------------

The reference is Panda's own test suite. A maintainer script runs it on a
fixed version of Panda and records every ``css()`` call it makes: the input,
the configuration, and the classes and CSS that Panda produced. The bundle's
tests replay each recorded call and compare the output. The
``CONTRIBUTING.md`` file of the package explains how to record them again.

What differs on purpose
-----------------------

* Styles are checked when Twig compiles the template, with the rules of
  Panda's generated TypeScript types, instead of by the TypeScript compiler.
* ``strict_tokens`` and ``strict_property_values`` are enabled by default.
  Panda disables ``strictTokens`` and ``strictPropertyValues`` by default. For
  the same options, both accept and refuse the same styles, with one
  exception: the CSS-wide keywords, ``inherit``, ``initial``, ``unset``,
  ``revert`` and ``revert-layer``, are accepted on every property. Panda's
  strict types refuse them on properties bound to tokens.
* A value cannot contain ``;``, ``{``, ``}``, a backslash, quotes, ``<`` or a
  CSS comment. So ``content: '"x"'`` is refused.
* With ``strict_tokens``, a category with no token refuses bare strings.
  Panda's types accept any string in that case.
* For a few unusual values, such as ``'0.50'``, repeated spaces or a ``base``
  key at the top of the hash, Panda's runtime and its build step give
  different class names. UX CSS names the classes of the values written in
  the template like the build step. That way, they always have their CSS.
* The configuration is written in YAML or PHP, under ``ux_css``. A token is
  written as its value. Panda's ``{ value: ... }`` form is accepted too.
* ``css()`` takes a single hash. Panda's ``css(a, b)``, which merges several
  style objects, is not supported.
* When the default value of a semantic color uses the ``/`` opacity syntax,
  its values for other conditions, such as ``_dark``, are kept. Panda drops
  them.
* In ``static_css``, ``*`` also works on a shorthand, such as ``p``.

Not supported yet
-----------------

These features of Panda CSS have no equivalent yet:

* recipes and slot recipes (``cva``), and patterns (``stack``, ``grid``...);
* text styles, layer styles and animation styles;
* themes, and the ``hash`` option for class names;
* global CSS, keyframes and custom utilities in the configuration;
* token categories beyond the twelve listed in :doc:`tokens`;
* importing tokens from a W3C design tokens file.

.. _`Panda CSS`: https://panda-css.com/
