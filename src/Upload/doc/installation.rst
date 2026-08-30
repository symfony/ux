Installation
============

TwigBundle enables the built-in form themes. Console enables ``ux:upload:cleanup``. Both integrations are optional package capabilities.

Install the Package
-------------------

.. caution::

    Before you start, make sure you have `StimulusBundle configured in your app`_.

.. code-block:: terminal

    $ composer require symfony/ux-upload

This initial release does not include a Symfony Flex recipe. Register the Bundle
and its routes explicitly::

    // config/bundles.php
    return [
        Symfony\UX\StimulusBundle\StimulusBundle::class => ['all' => true],
        Symfony\UX\Upload\UXUploadBundle::class => ['all' => true],
    ];

.. code-block:: yaml

    # config/routes/ux_upload.yaml
    ux_upload:
        resource: '@UXUploadBundle/config/routes.php'
        prefix: /_ux/upload
        trailing_slash_on_root: false

This route import uses ``/_ux/upload`` for the internal endpoints. Change
``prefix`` to use another path, and keep ``trailing_slash_on_root: false`` so
the direct endpoint has no trailing slash.

When TwigBundle is present, the Bundle prepends ``@UXUpload/form_theme.html.twig`` automatically.

Verify the installation:

.. code-block:: terminal

    $ php bin/console debug:router ux_upload_init
    $ php bin/console debug:router ux_upload_remove
    $ php bin/console debug:config ux_upload

Configure the Bundle
--------------------

.. code-block:: yaml

    # config/packages/ux_upload.yaml
    ux_upload:
        storage: local
        max_size: 100M

Assets
------

With AssetMapper:

.. code-block:: terminal

    $ php bin/console importmap:install

UX Upload does not load CSS automatically. Enable either
``@symfony/ux-upload/dist/compact.min.css`` or
``@symfony/ux-upload/dist/dropzone.min.css`` in ``assets/controllers.json``. See
:doc:`Customizing the Upload Field <customizing-upload-field>`.

With Webpack Encore or Reprise, install and build the npm dependencies:

.. code-block:: terminal

    $ npm install
    $ npm run dev

Filesystem and Secret
---------------------

The web process must be able to create, read and delete files below ``temp_dir`` and ``local_storage.directory``. Keep both outside ``public/``.

Signed URLs and tokens use ``kernel.secret``.

.. caution::

    Use the same stable ``APP_SECRET`` across application nodes. Rotating it
    invalidates outstanding transfers and form references.

Schedule cleanup:

.. code-block:: text

    */15 * * * * cd /path/to/app && php bin/console ux:upload:cleanup --age=24h

.. _`StimulusBundle configured in your app`: https://symfony.com/bundles/StimulusBundle/current/index.html
