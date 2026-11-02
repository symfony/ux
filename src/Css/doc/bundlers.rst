Loading the stylesheet
======================

The bundle writes one plain CSS file, ``var/ux_css/styles.css``. Any tool that
serves or bundles CSS can use it.

From your stylesheet
--------------------

Import the file at the top of your main stylesheet:

.. code-block:: css

    /* assets/styles/app.css */
    @layer reset, base, tokens, components, utilities;

    @import '../../var/ux_css/styles.css';

    /* ... your own styles */

The Symfony Flex recipe adds these two lines at the top of
``assets/styles/app.css``. They work the same way with AssetMapper, Symfony
Reprise and Webpack Encore.

The ``@layer`` statement comes first because the first stylesheet that names a
cascade layer sets the order of the layers. With this order, ``css()`` classes
win over the rules you write in the ``base`` and ``components`` layers. CSS
only accepts ``@import`` before any other rule, except ``@layer`` statements.
The section "The CSS layers" of :doc:`css-function` explains layers.

Importing the file from your stylesheet also lets editors that follow
``@import`` see the CSS variables of your tokens, so they can complete
``var(--colors-primary)`` in your own CSS.

AssetMapper
-----------

The bundle registers ``var/ux_css/`` as an AssetMapper path under the
``ux_css`` prefix. AssetMapper rewrites the ``@import`` path to the versioned
URL of the file, which the browser then loads after ``app.css``.

In dev, the bundle updates the file before Twig renders the page, so the
import always points to the current version. Its digest changes with its content,
so browsers never keep an old copy.

In production, ``asset-map:compile`` copies the file to ``public/assets/``,
like any other asset. Run it after ``cache:warmup``: see :doc:`production`.

Symfony Reprise and Webpack Encore
----------------------------------

The rules end up in the CSS file of the entry, which
``reprise_entry_link_tags()`` or ``encore_entry_link_tags()`` already loads.
This works with Symfony Reprise on Vite or Rsbuild, and with Webpack Encore.

In dev, the bundle rewrites the file during the request. Keep the watch mode or
the dev server of your bundler running, so it rebuilds when the file changes.

The file must exist before the bundler runs. In dev, the first request writes
it. On a fresh checkout or in CI, run ``cache:warmup`` before the build:

.. code-block:: terminal

    $ php bin/console cache:warmup
    $ npm run build

Minifiers may drop the ``@layer tokens, utilities;`` statement at the top of
the file. The two layers keep their order, since they appear in that order
anyway.
