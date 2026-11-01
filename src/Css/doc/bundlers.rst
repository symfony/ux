Loading the stylesheet
======================

The bundle writes one plain CSS file, ``var/ux_css/styles.css``. Any tool that
serves or bundles CSS can use it.

From the JavaScript entry
-------------------------

Import the file from a JavaScript entry:

.. code-block:: javascript

    // assets/app.js
    import './styles/app.css';
    import '../var/ux_css/styles.css';

The Symfony Flex recipe adds this line right after
``import './styles/app.css';``. It works the same way with AssetMapper, Symfony
Reprise and Webpack Encore.

The first stylesheet that names a cascade layer sets the order of the layers.
Importing UX CSS after your own styles lets the layers of your CSS (such as
``base``) be declared before the ``utilities`` layer, so ``css()`` classes win
over them. The section "The CSS layers" of :doc:`css-function` explains layers.

AssetMapper
-----------

The bundle registers ``var/ux_css/`` as an AssetMapper path under the
``ux_css`` prefix. ``importmap()`` adds a ``<link>`` tag for each CSS file that
the entry imports, this one included.

In dev, the bundle updates the file before Twig renders the page, so the link
always points to the current version. Its digest changes with its content,
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
