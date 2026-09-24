Installation
============

Install UX Upload, register its routes and enable its assets before adding an
upload field to a form. The steps below assume a Symfony application using
Twig and StimulusBundle.

Install the Package
-------------------

.. caution::

    Before you start, make sure you have `StimulusBundle configured in your app`_.

.. code-block:: terminal

    $ composer require symfony/ux-upload

This experimental package does not include a Symfony Flex recipe. Add these
entries to ``config/bundles.php``, keeping your existing bundles::

    // config/bundles.php
    return [
        Symfony\UX\StimulusBundle\StimulusBundle::class => ['all' => true],
        Symfony\UX\Upload\UXUploadBundle::class => ['all' => true],
    ];

.. configuration-block::

    .. code-block:: yaml

        # config/routes/ux_upload.yaml
        ux_upload:
            resource: '@UXUploadBundle/config/routes.php'
            prefix: /_ux/upload
            trailing_slash_on_root: false

    .. code-block:: php

        // config/routes/ux_upload.php
        use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

        return static function (RoutingConfigurator $routes): void {
            $routes->import('@UXUploadBundle/config/routes.php')
                ->prefix('/_ux/upload', false);
        };

This route import uses ``/_ux/upload`` for the internal endpoints. Change
``prefix`` to use another path, and keep ``trailing_slash_on_root: false`` so
the direct endpoint has no trailing slash.

UX Upload enables ``@UXUpload/form_theme.html.twig`` automatically when
TwigBundle is installed.

Verify the installation:

.. code-block:: terminal

    $ php bin/console debug:router ux_upload_init
    $ php bin/console debug:router ux_upload_remove
    $ php bin/console debug:config ux_upload

Identify Visitors
-----------------

By default, each upload must be associated with a user or tenant. This identity,
called the *upload context*, keeps visitors' uploads separate. With
SecurityBundle, the resolver uses the authenticated user. A firewall alone does
not identify a visitor: log in before trying the first upload. For public forms,
configure an application resolver that identifies each session, or explicitly
allow a shared anonymous context. See :doc:`Security <security>` before choosing
that behavior.

Configure the Bundle
--------------------

.. configuration-block::

    .. code-block:: yaml

        # config/packages/ux_upload.yaml
        ux_upload:
            storage: local
            max_size: 100M

    .. code-block:: php

        // config/packages/ux_upload.php
        use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

        return static function (ContainerConfigurator $container): void {
            $container->extension('ux_upload', [
                'storage' => 'local',
                'max_size' => '100M',
            ]);
        };

Request Limits
--------------

The default direct-upload threshold is 5 MiB. Ensure PHP accepts a multipart
file of that size and that the proxy accepts the full request including its
multipart overhead. Lower ``chunk_size`` when those limits cannot be increased.
See :doc:`Production <production>` for ``upload_max_filesize``,
``post_max_size`` and the distinction between direct and chunked requests.

Assets
------

With AssetMapper:

.. code-block:: terminal

    $ php bin/console importmap:install

UX Upload does not load CSS automatically. Enable
``@symfony/ux-upload/dist/style.min.css`` in ``assets/controllers.json``. See
:doc:`Customizing the Upload Field <customization>`.

With Webpack Encore or Reprise, install and build the npm dependencies:

.. code-block:: terminal

    $ npm install
    $ npm run dev

Temporary Files and Cleanup
---------------------------

The web process must be able to create, read and delete files below ``temp_dir``
and ``local_storage.directory``. Keep both outside ``public/``.

Signed URLs and tokens use ``kernel.secret``.

.. caution::

    Use the same stable ``APP_SECRET`` across application nodes. Rotating it
    invalidates outstanding transfers and form references.

Schedule cleanup:

.. code-block:: text

    */15 * * * * cd /path/to/app && php bin/console ux:upload:cleanup --age=24h

.. _`StimulusBundle configured in your app`: https://symfony.com/bundles/StimulusBundle/current/index.html
