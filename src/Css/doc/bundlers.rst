Loading the stylesheet
======================

The bundle writes one plain CSS file, ``var/ux_css/styles.css``. Any tool that
serves or bundles CSS can use it.

AssetMapper
-----------

The bundle registers ``var/ux_css/`` as an AssetMapper path under the
``ux_css`` prefix. Link the file from the base template:

.. code-block:: html+twig

    {# templates/base.html.twig #}
    {% block stylesheets %}
        <link rel="stylesheet" href="{{ asset('ux_css/styles.css') }}">
    {% endblock %}

In dev, the bundle updates the file before Twig renders the page, so the link
always points to the current version. Its digest changes with its content,
so browsers never keep an old copy.

In production, ``asset-map:compile`` copies the file to ``public/assets/``,
like any other asset. Run it after ``cache:warmup``: see :doc:`production`.

Webpack Encore and Symfony Reprise
----------------------------------

Import the file from a JavaScript entry, next to your own styles:

.. code-block:: javascript

    // assets/app.js
    import './styles/app.css';
    import '../var/ux_css/styles.css';

The rules end up in the CSS file of the entry, which
``encore_entry_link_tags()`` or ``reprise_entry_link_tags()`` already loads.
This works with Webpack Encore and with Symfony Reprise on Vite or Rsbuild.

With Webpack Encore, the file can also be an entry of its own:

.. code-block:: javascript

    // webpack.config.js
    Encore
        // ...
        .addStyleEntry('ux_css', './var/ux_css/styles.css')
    ;

.. code-block:: twig

    {# templates/base.html.twig #}
    {{ encore_entry_link_tags('ux_css') }}

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
