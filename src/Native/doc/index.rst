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
are written when you run the ``ux:native:build-configs`` command (see
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

    $ php bin/console ux:native:build-configs

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

Navigating the native stack
~~~~~~~~~~~~~~~~~~~~~~~~~~~

In a Hotwire Native app, the app manages the stack of screens and modals.
Consider a form opened in a modal, with the ``context: modal`` property of your path configuration.
After the form is submitted, a regular redirection displays the target page inside the modal, on top of a list that is now outdated.

Since version 1.2, Hotwire Native applications intercept three special paths.
When a redirection leads to one of them, the app does not display the page: it performs a navigation action instead.

==================================  ===================================================================================
Path                                Action in the app
==================================  ===================================================================================
``/recede_historical_location``     Dismisses the modal if there is one, otherwise goes back to the previous screen
``/refresh_historical_location``    Dismisses the modal if there is one, then reloads the visible screen
``/resume_historical_location``     Dismisses the modal if there is one, and does nothing else
==================================  ===================================================================================

First, import the routes that answer these paths:

.. code-block:: yaml

    # config/routes/ux_native.yaml
    ux_native:
        resource: '@UXNativeBundle/config/routes.php'

Then, in the controller that handles the form, use the ``NativeNavigator`` service.
Its ``redirectOrToRoute()`` method sends the app to the historical location, and a browser to the given route::

    use Symfony\UX\Native\Navigation\HistoricalLocation;
    use Symfony\UX\Native\Navigation\NativeNavigator;

    #[Route('/notes/new', name: 'app_note_new', methods: ['GET', 'POST'])]
    public function new(Request $request, NativeNavigator $navigator): Response
    {
        // ... handle the form

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', 'Note created!');

            // In the app: dismisses the modal and reloads the list under it
            // In a browser: redirects to the list
            return $navigator->redirectOrToRoute(HistoricalLocation::Refresh, 'app_notes');
        }

        // ...
    }

The service provides three methods of this kind:

* ``redirectOrToRoute(HistoricalLocation $location, string $route, array $parameters = [])`` redirects a browser to a route;
* ``redirectOr(HistoricalLocation $location, string $url)`` redirects a browser to a URL;
* ``redirectOrBackOr(HistoricalLocation $location, string $fallbackUrl)`` redirects a browser back to the page in the ``Referer`` header.
  The browser only follows a ``Referer`` with the same origin (scheme, host and port) as the current request.
  Otherwise, it goes to the fallback URL.

They all redirect with the ``303 See Other`` status code, so the next request is a ``GET``, as Turbo expects after a form submission.
Flash messages stay in the session, so they are displayed on the screen that the app reloads.

When you need to decide yourself, use the ``isNative()`` and ``redirect()`` methods::

    if ($navigator->isNative()) {
        return $navigator->redirect(HistoricalLocation::Recede);
    }

    return $this->render('note/show.html.twig', [
        // ...
    ]);

.. note::

    A browser never receives a historical location.
    The ``redirectOr*()`` methods only redirect to it when the current request comes from a Hotwire Native app (see `Detecting Native Requests`_), and ``redirect()`` throws a ``LogicException`` otherwise.
    A browser that opens one of these paths anyway, from its history or a bookmark, gets a short page with the ``X-Robots-Tag: noindex`` header.

.. note::

    The applications look for these paths anywhere in the URL, so you can import the routes with a prefix: ``/app/refresh_historical_location`` works.
    Do not change the paths themselves: the applications only recognize these exact names.

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

.. _`the Symfony UX initiative`: https://ux.symfony.com/
.. _`Hotwire Native`: https://native.hotwired.dev/
.. _`Hotwire Native Bridge`: https://native.hotwired.dev/reference/bridge-installation
.. _`Hotwire Native Bridge documentation`: https://native.hotwired.dev/reference/bridge-installation
