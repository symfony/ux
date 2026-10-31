Symfony UX CSS
==============

.. caution::

    **EXPERIMENTAL** This bundle is currently experimental and is likely to
    change, possibly significantly, before its first stable release.

Symfony UX CSS gives Twig a ``css()`` function inspired by `Panda CSS`_. You
pass it a hash of style properties, and it returns atomic class names built
from your design tokens:

.. code-block:: html+twig

    {# templates/components/Card.html.twig #}
    <div class="{{ css({
        p: 'md',
        color: 'fg',
        _hover: { color: 'primary' },
        md: { p: 'lg' }
    }) }}">
        ...
    </div>

The attribute becomes ``class="p_md c_fg hover:c_primary md:p_lg"``, and the
bundle writes the matching rules to one CSS file:

.. code-block:: css

    .p_md { padding: var(--spacing-md); }
    .c_fg { color: var(--colors-fg); }
    .hover\:c_primary:is(:hover, [data-hover]) { color: var(--colors-primary); }

    @media screen and (min-width: 48rem) {
        .md\:p_lg { padding: var(--spacing-lg); }
    }

It is an alternative to utility classes written as strings. The hash is
checked when Twig compiles the template: a misspelled property, an unknown
token or a wrong keyword is a Twig syntax error that points to the template
line. You find the mistake when you open the page, and ``lint:twig`` finds it
in CI, before it reaches production.

The bundle needs no Node.js. PHP reads the templates and writes the CSS file.
AssetMapper serves it as is, and Webpack Encore or Symfony Reprise can import
it like any other stylesheet.

Mental model
------------

=================================== ============================================
Question                            Answer
=================================== ============================================
When is ``css()`` resolved?         When Twig compiles the template, for every
                                    value written in the template. Values only
                                    known at runtime are resolved when the
                                    template renders.
Where does the CSS go?              In ``var/ux_css/styles.css``, one file for
                                    the whole application.
Who writes the file?                In dev, the bundle, before the response of
                                    any request that follows a template change.
                                    For production, ``cache:warmup``.
What does the file contain?         Every style written in a template, the
                                    styles listed in ``static_css``, and every
                                    token as a CSS variable.
When are mistakes reported?         When Twig compiles the template: on the page
                                    in dev, in ``lint:twig`` and in
                                    ``cache:warmup``.
=================================== ============================================

Start here
----------

* :doc:`installation` lists the requirements and installs the bundle.
* :doc:`getting-started` declares a few tokens and styles a first component.
* :doc:`alternatives` compares ``css()`` with Tailwind CSS, UnoCSS, the
  Bootstrap utilities and hand-written CSS.

Write styles
------------

* :doc:`tokens` declares colors, spacing and the other design tokens.
* :doc:`conditions` covers hover, dark mode, other conditions, and
  breakpoints.
* :doc:`css-function` is the reference for the ``css()`` syntax and values.
* :doc:`validation` explains which mistakes are caught, and where.
* :doc:`editors` covers the types generated for editors and static analysis.
* :doc:`dynamic-styles` covers values only known at runtime and
  ``static_css``.

Ship it
-------

* :doc:`bundlers` loads the stylesheet with AssetMapper, Webpack Encore or
  Symfony Reprise.
* :doc:`production` covers the deployment order and the measured performance.
* :doc:`configuration` is the complete configuration reference.
* :doc:`panda` lists what differs from Panda CSS and what is not supported
  yet.

The bundle supports PHP 8.4 or later, Symfony 7.4 or 8.x, and Twig 3.24 or
later.

.. _`Panda CSS`: https://panda-css.com/
