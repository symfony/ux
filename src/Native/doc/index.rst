Symfony UX Native
=================

**EXPERIMENTAL** This component is currently experimental and is likely
to change, or even change drastically.

Symfony UX Native is a Symfony bundle integrating `Hotwire Native`_ into Symfony
applications. It is part of `the Symfony UX initiative`_.

`Hotwire Native`_ is a framework for building native mobile applications (iOS and Android)
that wrap your web application in a native shell. This bundle provides tools to:

- **Detect native requests** automatically, based on the ``User-Agent`` header;
- **Conditionally render content** in Twig templates depending on whether the
  request comes from a native app or a browser;
- **Generate JSON configuration files** consumed by Hotwire Native mobile clients
  (path rules, settings, etc.);
- **Scaffold Bridge controllers** to enable communication between your web app
  and the native shell via Stimulus.

Installation
------------

.. caution::

    Before you start, make sure you have `StimulusBundle configured in your app`_.

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-native

If you're using WebpackEncore, install your assets and restart Encore (not
needed if you're using AssetMapper):

.. code-block:: terminal

    $ npm install --force
    $ npm run watch

Configuration
-------------

The bundle exposes a single configuration option:

.. code-block:: yaml

    # config/packages/ux_native.yaml
    ux_native:
        output_dir: '%kernel.project_dir%/public' # default

The ``output_dir`` option defines the directory where JSON configuration files
are written when you run the ``ux-native:dump`` command (see
`Dumping Configurations for Production`_).

Usage
-----

Detecting Native Requests
~~~~~~~~~~~~~~~~~~~~~~~~~

The bundle automatically listens to every incoming request and inspects the
``User-Agent`` header. If it contains the string ``Hotwire Native`` (which is
sent by all Hotwire Native iOS and Android clients), the request is flagged as
a native request.

You do not need any additional configuration for this to work -- it is enabled
by default.

In Twig templates, use the ``ux_is_native()`` function to conditionally render
content:

.. code-block:: html+twig

    {% if ux_is_native() %}
        {# Content only visible in the native app #}
        <p>Welcome to our mobile app!</p>
    {% else %}
        {# Content only visible in the browser #}
        <nav>
            <a href="/">Home</a>
            <a href="/about">About</a>
        </nav>
    {% endif %}

This is useful for hiding navigation bars, footers, or any elements that the
native shell already provides.

In PHP, you can also check the request attribute directly::

    use Symfony\UX\Native\EventListener\NativeListener;

    // In a controller
    public function index(Request $request): Response
    {
        $isNative = $request->attributes->get(NativeListener::NATIVE_ATTRIBUTE, false);

        // ...
    }

Creating Configurations
~~~~~~~~~~~~~~~~~~~~~~~

Hotwire Native mobile clients can consume JSON configuration files to control
their behavior (e.g. path rules, pull-to-refresh, local database usage, etc.).
This bundle lets you define those configurations as PHP objects and register
them as Symfony services.

A configuration is built using two classes:
``Symfony\UX\Native\Configuration\Configuration`` and
``Symfony\UX\Native\Configuration\Rule``.

Using PHP Attributes
^^^^^^^^^^^^^^^^^^^^

The easiest way to register configurations is to use the
``#[AsNativeConfigurationProvider]`` and ``#[AsNativeConfiguration]`` attributes.

Create a class annotated with ``#[AsNativeConfigurationProvider]``, and add
``#[AsNativeConfiguration('/path/to/config.json')]`` on each method that returns
a ``Configuration`` object::

    // src/Native/AppNativeConfiguration.php
    namespace App\Native;

    use Symfony\UX\Native\Attribute\AsNativeConfiguration;
    use Symfony\UX\Native\Attribute\AsNativeConfigurationProvider;
    use Symfony\UX\Native\Configuration\Configuration;
    use Symfony\UX\Native\Configuration\Rule;

    #[AsNativeConfigurationProvider]
    final class AppNativeConfiguration
    {
        #[AsNativeConfiguration('/config/ios_v1.json')]
        public function iosV1(): Configuration
        {
            return new Configuration(
                settings: [
                    'use_local_db' => true,
                    'cable' => [
                        'script_url' => 'https://example.com/cable.js',
                    ],
                ],
                rules: [
                    new Rule(
                        patterns: ['.*'],
                        properties: [
                            'context' => 'default',
                            'pull_to_refresh_enabled' => true,
                        ],
                    ),
                ],
            );
        }

        #[AsNativeConfiguration('/config/android_v1.json')]
        public function androidV1(): Configuration
        {
            return new Configuration(
                settings: [
                    'use_local_db' => false,
                ],
                rules: [
                    new Rule(
                        patterns: ['/articles/.*'],
                        properties: [
                            'context' => 'default',
                            'pull_to_refresh_enabled' => false,
                        ],
                    ),
                ],
            );
        }
    }

That's it! The class is automatically discovered and each annotated method is
registered as a configuration at the given path.

.. note::

    The annotated methods must be **public** and **non-static**, and must
    declare a return type of ``Configuration``.

Using Service Tags
^^^^^^^^^^^^^^^^^^

Alternatively, you can register configurations manually using service tags. This
is useful if you need to use a factory pattern or if you prefer YAML
configuration::

    // src/Native/IosConfigurationFactory.php
    namespace App\Native;

    use Symfony\UX\Native\Configuration\Configuration;
    use Symfony\UX\Native\Configuration\Rule;

    final class IosConfigurationFactory
    {
        public static function v1(): Configuration
        {
            return new Configuration(
                settings: [
                    'use_local_db' => true,
                    'cable' => [
                        'script_url' => 'https://example.com/cable.js',
                    ],
                ],
                rules: [
                    new Rule(
                        patterns: ['.*'],
                        properties: [
                            'context' => 'default',
                            'pull_to_refresh_enabled' => true,
                        ],
                    ),
                ],
            );
        }
    }

Then register it as a service tagged with ``ux_native.configuration``, and
specify the ``path`` at which it should be served:

.. code-block:: yaml

    # config/services.yaml
    services:
        App\Native\IosConfigurationFactory: ~

        app.native.ios_v1:
            class: Symfony\UX\Native\Configuration\Configuration
            factory: ['App\Native\IosConfigurationFactory', 'v1']
            tags:
                - { name: 'ux_native.configuration', path: '/config/ios_v1.json' }

You can register as many configurations as you need (e.g. one per platform, one
per version, etc.), each with a different ``path``.

The resulting JSON file will look like this:

.. code-block:: json

    {
        "settings": {
            "use_local_db": true,
            "cable": {
                "script_url": "https://example.com/cable.js"
            }
        },
        "rules": [
            {
                "patterns": [
                    ".*"
                ],
                "properties": {
                    "context": "default",
                    "pull_to_refresh_enabled": true
                }
            }
        ]
    }

Serving Configurations in Development
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In **debug mode** (i.e. the ``dev`` environment), the bundle automatically
serves configuration JSON responses dynamically. When a request matches a
registered configuration path (e.g. ``/config/ios_v1.json``), the response is
generated on the fly from the ``Configuration`` object -- no need to dump files
to disk.

This means you can iterate on your configurations without running any command.
Simply change your factory code and refresh.

Dumping Configurations for Production
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In **production**, you should dump the configuration files to disk so they can
be served as static files by your web server, without going through the Symfony
kernel.

Run the following command:

.. code-block:: terminal

    $ php bin/console ux-native:dump

This writes all registered configurations as JSON files into the ``output_dir``
directory (defaults to ``public/``). For example, a configuration registered at
path ``/config/ios_v1.json`` will be written to ``public/config/ios_v1.json``.

.. tip::

    Add this command to your deployment pipeline (e.g. in your ``composer.json``
    post-install scripts or your CI/CD configuration) to ensure the configuration
    files are always up to date.

Creating Bridge Controllers
~~~~~~~~~~~~~~~~~~~~~~~~~~~

`Hotwire Native Bridge`_ allows your web application and the native shell to
communicate via Stimulus controllers. The bundle provides a Maker command to
quickly scaffold a Bridge controller.

.. note::

    This command requires ``symfony/maker-bundle`` to be installed.

.. code-block:: terminal

    $ php bin/console make:native-bridge-controller

The command will ask you for the controller name and generate a JavaScript file
in ``assets/controllers/``. For example, creating a controller named
``contact_form`` will generate the following file:

.. code-block:: javascript

    // assets/controllers/contact_form_controller.js
    import { BridgeComponent } from "@hotwired/hotwire-native-bridge"

    export default class extends BridgeComponent {
        static component = "contact_form"

        connect() {
            super.connect()
            // The bridge is now ready and will handle communication
            // between your web app and the native application
        }

        disconnect() {
            super.disconnect()
            // Clean up any resources or event listeners here
        }
    }

You can then use this controller in your Twig templates:

.. code-block:: html+twig

    <form data-controller="contact-form">
        {# ... #}
    </form>

For more information on how Bridge components work, refer to the
`Hotwire Native Bridge documentation`_.

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

.. _`the Symfony UX initiative`: https://ux.symfony.com/
.. _`Hotwire Native`: https://native.hotwired.dev/
.. _`Hotwire Native Bridge`: https://native.hotwired.dev/reference/bridge-installation
.. _`Hotwire Native Bridge documentation`: https://native.hotwired.dev/reference/bridge-installation
.. _`StimulusBundle configured in your app`: https://symfony.com/bundles/StimulusBundle/current/index.html
