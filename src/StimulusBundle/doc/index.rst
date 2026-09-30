StimulusBundle: Symfony integration with Stimulus
=================================================

.. tip::

    Check out live demos of Symfony UX at https://ux.symfony.com!

This bundle adds integration between Symfony, `Stimulus`_ and the Symfony UX packages:

* Twig ``stimulus_`` functions & filters to add Stimulus controllers,
  actions & targets in your templates;
* Integration to load :ref:`UX Packages <ux-packages>` (extra Stimulus controllers)

Installation
------------

First, if you don't have one yet, choose and install an asset handling system;
they all work great with StimulusBundle:

* `AssetMapper`_: PHP-based system for handling assets

* `Webpack Encore`_: Node-based packaging system built on Webpack

* `Reprise`_: Node-based integration for Vite and Rsbuild (experimental)

See `Reprise vs Encore vs AssetMapper`_ to learn which is best for your project.

Next, install the bundle:

.. code-block:: terminal

    $ composer require symfony/stimulus-bundle

If you're using `Symfony Flex`_, you're done! The recipe will update the
necessary files. If not, or you're curious, see :ref:`Manual Setup <manual-installation>`.

.. tip::

    If you're using Encore, be sure to install your assets (e.g. ``npm install``)
    and restart Encore.

Usage
-----

You can now create custom Stimulus controllers inside of the ``assets/controllers``
directory. In fact, you should have an example controller there already: ``hello_controller.js``:

.. code-block:: javascript

    import { Controller } from '@hotwired/stimulus';

    export default class extends Controller {
        connect() {
            this.element.textContent = 'Hello Stimulus! Edit me in assets/controllers/hello_controller.js';
        }
    }

Then, activate the controller in your HTML:

.. code-block:: html+twig

    <div data-controller="hello">
       ...
    </div>

Optionally, this bundle has a Twig function to render the attribute:

.. code-block:: html+twig

    <div {{ stimulus_controller('hello') }}>
        ...
    </div>

    <!-- would render -->
    <div data-controller="hello">
       ...
    </div>

That's it! Whenever this element appears on the page, the ``hello`` controller
will activate.

There's a *lot* more to learn about Stimulus. See the `Stimulus Documentation`_
for all the goodies.

TypeScript Controllers
~~~~~~~~~~~~~~~~~~~~~~

If you want to use `TypeScript`_ to define your controllers, you can! Install and set up the
`sensiolabs/typescript-bundle`_. Then be sure to add the ``assets/controllers`` path to the
``sensiolabs_typescript.source_dir`` configuration. Finally, create your controller in that
directory and you're good to go.

.. _ux-packages:

The UX Packages
~~~~~~~~~~~~~~~

Symfony provides a set of UX packages that add extra Stimulus controllers to solve
common problems. StimulusBundle activates any 3rd party Stimulus controllers
that are mentioned in your ``assets/controllers.json`` file. This file is updated
whenever you install a UX package.

Check out the `official UX packages`_.

Lazy Stimulus Controllers
~~~~~~~~~~~~~~~~~~~~~~~~~

By default, all of your controllers (i.e. files in ``assets/controllers/`` +
controllers in ``assets/controllers.json``) will be downloaded and loaded on
every page.

Sometimes you may have a controller that's only used on some pages. In that case,
you can make the controller "lazy". In this case, will *not* be downloaded on
initial page load. Instead, as soon as an element appears on the page matching
the controller (e.g. ``<div data-controller="hello">``), the controller - and anything
else it imports - will be lazily-loaded via Ajax.

To make one of your custom controllers lazy, add a special comment on top:

.. code-block:: javascript

    import { Controller } from '@hotwired/stimulus';

    /* stimulusFetch: 'lazy' */
    export default class extends Controller {
        // ...
    }

To make a third-party controller lazy, in ``assets/controllers.json``, set
``fetch`` to ``lazy``.

.. note::

    If you write your controllers using TypeScript and you're using
    StimulusBundle 2.21.0 or earlier, make sure ``removeComments`` is not set
    to ``true`` in your TypeScript config.

Stimulus Tools around the World
-------------------------------

Because Stimulus is used by developers outside of Symfony, many tools
exist beyond the UX packages:

* `stimulus-use`_: Add composable behaviors to your Stimulus controllers, like
  debouncing, detecting outside clicks and many other things.

* `stimulus-components`_ A large number of pre-made Stimulus controllers, like for
  Copying to clipboard, Sortable, Popover (similar to tooltips) and much more.

Stimulus Twig Helpers
---------------------

This bundle adds some Twig functions/filters to help add Stimulus controllers,
actions and targets in your templates.

.. note::

    Though this bundle provides these helpful Twig functions/filters, it's
    recommended to use raw data attributes instead, as they're straightforward.

.. tip::

    If you use PhpStorm IDE - you may want to install `Stimulus plugin`_
    to get nice auto-completion for the attributes.

stimulus_controller
~~~~~~~~~~~~~~~~~~~

This bundle ships with a special ``stimulus_controller()`` Twig function
that can be used to render `Stimulus Controllers & Values`_ and `CSS Classes`_.
Stimulus Controllers can also reference other controllers by using `Outlets`_.

For example:

.. code-block:: html+twig

    <div {{ stimulus_controller('hello', { 'name': 'World', 'data': [1, 2, 3, 4] }) }}>
        Hello
    </div>

    <!-- would render -->
    <div
       data-controller="hello"
       data-hello-name-value="World"
       data-hello-data-value="&#x5B;1,2,3,4&#x5D;"
    >
       Hello
    </div>

If you want to set CSS classes:

.. code-block:: html+twig

    <div {{ stimulus_controller('hello', { 'name': 'World', 'data': [1, 2, 3, 4] }, { 'loading': 'spinner' }) }}>
        Hello
    </div>

    <!-- would render -->
    <div
       data-controller="hello"
       data-hello-name-value="World"
       data-hello-data-value="&#x5B;1,2,3,4&#x5D;"
       data-hello-loading-class="spinner"
    >
       Hello
    </div>

    <!-- or without values -->
    <div {{ stimulus_controller('hello', controllerClasses: { 'loading': 'spinner' }) }}>
        Hello
    </div>

And with outlets:

.. code-block:: html+twig

    <div {{ stimulus_controller('hello',
            { 'name': 'World', 'data': [1, 2, 3, 4] },
            { 'loading': 'spinner' },
            { 'other': '.target' } ) }}>
        Hello
    </div>

    <!-- would render -->
    <div
       data-controller="hello"
       data-hello-name-value="World"
       data-hello-data-value="&#x5B;1,2,3,4&#x5D;"
       data-hello-loading-class="spinner"
       data-hello-other-outlet=".target"
    >
       Hello
    </div>

    <!-- or without values/classes -->
    <div {{ stimulus_controller('hello', controllerOutlets: { 'other': '.target' }) }}>
        Hello
    </div>

Any non-scalar values (like ``data: [1, 2, 3, 4]``) are JSON-encoded. And all
values are properly escaped (the string ``&#x5B;`` is an escaped
``[`` character, so the attribute is really ``[1,2,3,4]``).

If you have multiple controllers on the same element, you can chain them as
there's also a ``stimulus_controller`` filter:

.. code-block:: html+twig

    <div {{ stimulus_controller('hello', { 'name': 'World' })|stimulus_controller('other-controller') }}>
        Hello
    </div>

    <!-- would render -->
    <div data-controller="hello other-controller" data-hello-name-value="World">
        Hello
    </div>

You can also retrieve the generated attributes as an array, which can be helpful e.g. for forms:

.. code-block:: twig

    {{ form_start(form, { attr: stimulus_controller('hello', { 'name': 'World' }).toArray() }) }}

stimulus_action
~~~~~~~~~~~~~~~

The ``stimulus_action()`` Twig function can be used to render `Stimulus Actions`_.

For example:

.. code-block:: html+twig

    <div {{ stimulus_action('controller', 'method') }}>Hello</div>
    <div {{ stimulus_action('controller', 'method', 'click') }}>Hello</div>

    <!-- would render -->
    <div data-action="controller#method">Hello</div>
    <div data-action="click->controller#method">Hello</div>

If you have multiple actions and/or methods on the same element, you can chain
them as there's also a ``stimulus_action`` filter:

.. code-block:: html+twig

    <div {{ stimulus_action('controller', 'method')|stimulus_action('other-controller', 'test') }}>
        Hello
    </div>

    <!-- would render -->
    <div data-action="controller#method other-controller#test">
        Hello
    </div>

You can also retrieve the generated attributes as an array, which can be helpful e.g. for forms:

.. code-block:: twig

    {{ form_row(form.password, { attr: stimulus_action('hello-controller', 'checkPasswordStrength').toArray() }) }}

You can also pass `parameters`_ to actions:

.. code-block:: html+twig

    <div {{ stimulus_action('hello-controller', 'method', 'click', { 'count': 3 }) }}>Hello</div>

    <!-- would render -->
    <div data-action="click->hello-controller#method" data-hello-controller-count-param="3">Hello</div>

stimulus_target
~~~~~~~~~~~~~~~

The ``stimulus_target()`` Twig function can be used to render `Stimulus Targets`_.

For example:

.. code-block:: html+twig

    <div {{ stimulus_target('controller', 'myTarget') }}>Hello</div>
    <div {{ stimulus_target('controller', 'myTarget secondTarget') }}>Hello</div>

    <!-- would render -->
    <div data-controller-target="myTarget">Hello</div>
    <div data-controller-target="myTarget secondTarget">Hello</div>

If you have multiple targets on the same element, you can chain them as there's
also a ``stimulus_target`` filter:

.. code-block:: html+twig

    <div {{ stimulus_target('controller', 'myTarget')|stimulus_target('other-controller', 'anotherTarget') }}>
        Hello
    </div>

    <!-- would render -->
    <div data-controller-target="myTarget" data-other-controller-target="anotherTarget">
        Hello
    </div>

You can also retrieve the generated attributes as an array, which can be helpful e.g. for forms:

.. code-block:: twig

    {{ form_row(form.password, { attr: stimulus_target('hello-controller', 'myTarget').toArray() }) }}

Chaining Different Helpers
~~~~~~~~~~~~~~~~~~~~~~~~~~

The ``stimulus_controller``, ``stimulus_action`` and ``stimulus_target``
filters can be mixed freely to render all the attributes of an element at
once, whichever function the chain started with:

.. code-block:: html+twig

    <div {{ stimulus_controller('first-controller')
        |stimulus_target('second-controller', 'anotherTarget')
        |stimulus_target('third-controller', 'foo')
        |stimulus_action('first-controller', 'test')
        |stimulus_controller('fourth-controller')
        |stimulus_action('fourth-controller', 'onClick') }}
    >
        Hello
    </div>

    <!-- would render -->
    <div data-controller="first-controller fourth-controller"
        data-action="first-controller#test fourth-controller#onClick"
        data-second-controller-target="anotherTarget"
        data-third-controller-target="foo"
    >
        Hello
    </div>

Stimulus Attributes from PHP
----------------------------

The same attributes are available from PHP through the ``StimulusHelper``
service, which you can autowire. Reach for it when the element you want to
decorate is not written in a template, a form field for instance::

    // src/Form/EventType.php
    namespace App\Form;

    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\Extension\Core\Type\CountryType;
    use Symfony\Component\Form\FormBuilderInterface;
    use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

    class EventType extends AbstractType
    {
        public function __construct(private StimulusHelper $stimulusHelper)
        {
        }

        public function buildForm(FormBuilderInterface $builder, array $options): void
        {
            $attributes = $this->stimulusHelper->createStimulusAttributes();
            $attributes->addController('country-picker', ['locale' => 'fr']);
            $attributes->addTarget('country-picker', 'select');
            $attributes->addAction('country-picker', 'refresh', 'change');

            $builder->add('country', CountryType::class, [
                'attr' => $attributes->toArray(),
            ]);
        }
    }

The field then renders with the attributes the Twig helpers would have
produced:

.. code-block:: html

    <select
        data-controller="country-picker"
        data-action="change->country-picker#refresh"
        data-country-picker-target="select"
        data-country-picker-locale-value="fr"
    >

Cast the object to a string when you need the rendered attributes rather than
an array, and use ``addAttribute()`` to carry along an attribute that is not a
Stimulus one.

.. _configuration:

Configuration
-------------

If you're using `AssetMapper`_, you can configure the path to your controllers
directory and the ``controllers.json`` file if you need to use different paths:

.. code-block:: yaml

    # config/packages/stimulus.yaml
    stimulus:
        # the default values
        controller_paths:
            - '%kernel.project_dir%/assets/controllers'
        controllers_json: '%kernel.project_dir%/assets/controllers.json'
        # no application by default
        applications:
            # the application name: lowercase letters, digits, "_" and "-", "default" is reserved
            admin:
                # required: an empty ".js" file inside an AssetMapper path
                loader: '%kernel.project_dir%/assets/admin/stimulus_loader.js'
                controller_paths: []
                include_global_paths: true
                # null (the global controllers_json file) or the path of a file
                # that replaces it for this application
                controllers_json: ~
                # true to merge the controllers_json file of the application over
                # the global one instead of replacing it (requires controllers_json)
                merge_controllers_json: false

See :ref:`Scoping Controllers with AssetMapper <assetmapper-applications>` for
the ``applications`` option.

.. _manual-installation:

Manual Installation Details
---------------------------

When you install this bundle, its Flex recipe should handle updating all the files
needed. If you're not using Flex or want to double-check the changes, check out
the `StimulusBundle Flex recipe`_. Here's a summary of what's inside:

* ``assets/stimulus_bootstrap.js`` starts the Stimulus application and loads your
  controllers. It's imported by ``assets/app.js`` and its exact content
  depends on whether you have Webpack Encore or AssetMapper installed
  (see below).

* ``assets/app.js`` is *updated* to import ``assets/stimulus_bootstrap.js``

* ``assets/controllers.json`` This file starts (mostly) empty and is automatically
  updated as your install UX packages that provide Stimulus controllers.

* ``assets/controllers/`` This directory is where you should put your custom Stimulus
  controllers. It comes with one example ``hello_controller.js`` file.

A few other changes depend on which asset system you're using:

With AssetMapper
~~~~~~~~~~~~~~~~

If you're using AssetMapper, two new entries will be added to your ``importmap.php``
file::

    // importmap.php
    return [
        // ...

        '@symfony/stimulus-bundle' => [
            'path' => '@symfony/stimulus-bundle/loader.js',
        ],
        '@hotwired/stimulus' => [
            'version' => '3.2.2',
        ],
    ];

The recipe will update your ``assets/stimulus_bootstrap.js`` file to look like this:

.. code-block:: javascript

    // assets/stimulus_bootstrap.js
    import { startStimulusApp } from '@symfony/stimulus-bundle';

    const app = startStimulusApp();

The ``@symfony/stimulus-bundle`` refers the one of the new entries in your
``importmap.php`` file. This file is dynamically built by the bundle and
will import all your custom controllers as well as those from ``controllers.json``.
It will also dynamically enable "debug" mode in Stimulus when your application
is running in debug mode.


With WebpackEncoreBundle
~~~~~~~~~~~~~~~~~~~~~~~~

If you're using Webpack Encore, the recipe will also update your ``webpack.config.js``
file to include this line:

.. code-block:: javascript

    // webpack.config.js
    .enableStimulusBridge('./assets/controllers.json')

The ``assets/stimulus_bootstrap.js`` file will be updated to look like this:

.. code-block:: javascript

    // assets/stimulus_bootstrap.js
    import { startStimulusApp } from '@symfony/stimulus-bridge';

    // Registers Stimulus controllers from controllers.json and in the controllers/ directory
    export const app = startStimulusApp(require.context(
        '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
        true,
        /\.[jt]sx?$/
    ));

The ``@hotwired/stimulus`` package will be added to your ``package.json`` file.
The Webpack Encore integration also relies on `@symfony/stimulus-bridge`_, which is specific to Encore,
so install it yourself:

.. code-block:: terminal

    $ npm install --save-dev @symfony/stimulus-bridge

With Reprise
~~~~~~~~~~~~

If you're using `Reprise`_, you must enable Stimulus by pointing the Reprise plugin at your ``controllers.json`` file:

.. code-block:: javascript

    // vite.config.js (or rsbuild.config.js)
    import Symfony from '@symfony/reprise/vite';

    export default defineConfig({
        plugins: [
            Symfony({
                stimulus: './assets/controllers.json',
            }),
        ],
    });

The ``assets/stimulus_bootstrap.js`` file will be updated to look like this:

.. code-block:: javascript

    // assets/stimulus_bootstrap.js
    import { startStimulusApp } from '@symfony/reprise/stimulus';

    const app = startStimulusApp();

The ``@symfony/reprise/stimulus`` helper starts the application and registers all your
custom controllers along with those from ``controllers.json``, eager or lazy, the same
way the AssetMapper loader does. The Stimulus runtime ships with the ``@symfony/reprise``
package, so ``@hotwired/stimulus`` is the only extra package you need to install.

How are the Stimulus Controllers Loaded?
----------------------------------------

When you install a UX PHP package, Symfony Flex will automatically update your
``package.json`` file (not done or needed if using AssetMapper) to point to a
"virtual package" that lives inside that PHP package. For example:

.. code-block:: json

    {
        "devDependencies": {
            "...": "",
            "@symfony/ux-chartjs": "file:vendor/symfony/ux-chartjs/assets"
        }
    }

This gives you a *real* Node package (e.g. ``@symfony/ux-chartjs``) that, instead
of being downloaded, points directly to files that already live in your ``vendor/``
directory.

The Flex recipe will usually also update your ``assets/controllers.json`` file
to add a new Stimulus controller to your app. For example:

.. code-block:: json

    {
        "controllers": {
            "@symfony/ux-chartjs": {
                "chart": {
                    "enabled": true,
                    "fetch": "eager"
                }
            }
        },
        "entrypoints": []
    }

Finally, your ``assets/stimulus_bootstrap.js`` file will automatically register:

* All files in ``assets/controllers/`` as Stimulus controllers;
* And all controllers described in ``assets/controllers.json`` as Stimulus controllers.

.. note::

    If you're using WebpackEncore, the ``stimulus_bootstrap.js`` file works in partnership
    with `@symfony/stimulus-bridge`_. With AssetMapper, the ``stimulus_bootstrap.js`` file
    works directly with this bundle: a ``@symfony/stimulus-bundle`` entry is added
    to your ``importmap.php`` file via Flex, which points to a file that is dynamically
    built to find and load your controllers (see :ref:`Configuration <configuration>`).

The end result: you install a package, and you instantly have a Stimulus
controller available! In this example, it's called
``@symfony/ux-chartjs/chart``. Well, technically, it will be called
``symfony--ux-chartjs--chart``. However, you can pass the original name
into the ``{{ stimulus_controller() }}`` function from WebpackEncoreBundle, and
it will normalize it:

.. code-block:: html+twig

    <div {{ stimulus_controller('@symfony/ux-chartjs/chart') }}>

    <!-- will render as: -->
    <div data-controller="symfony--ux-chartjs--chart">


Loading Different Controllers per Part of Your App
--------------------------------------------------

An application can have distinct areas, for example a public site and an
admin back office. By default, every registered controller loads on every
page, so eagerly loaded controllers from one area ship to the other.

AssetMapper, Webpack Encore and Reprise all let you load a distinct set of
controllers per area. With AssetMapper, see
:ref:`Scoping Controllers with AssetMapper <assetmapper-applications>`.

.. _assetmapper-applications:

Scoping Controllers with AssetMapper
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

With AssetMapper, declare one Stimulus application per area under the
``applications`` option. Each application gets its own loader, which only
imports and preloads the controllers of that application:

.. code-block:: yaml

    # config/packages/stimulus.yaml
    stimulus:
        applications:
            admin:
                loader: '%kernel.project_dir%/assets/admin/stimulus_loader.js'
                controller_paths: ['%kernel.project_dir%/assets/admin/controllers']
                include_global_paths: false
                controllers_json: '%kernel.project_dir%/assets/admin/controllers.json'

The application name must start with a lowercase letter and contain only
lowercase letters, digits, ``_`` and ``-``. The name ``default`` is reserved.

Create the loader as an empty file, inside a path mapped by AssetMapper. The
file must exist and each application needs its own loader. The bundle
replaces its content when the asset is compiled:

.. code-block:: terminal

    $ mkdir -p assets/admin && touch assets/admin/stimulus_loader.js

Then add two entries to your ``importmap.php`` file: one for the loader and
one for the entrypoint of the area::

    // importmap.php
    return [
        // ...

        'admin' => [
            'path' => './assets/admin.js',
            'entrypoint' => true,
        ],
        '@symfony/stimulus-bundle/admin' => [
            'path' => './assets/admin/stimulus_loader.js',
        ],
    ];

The entrypoint starts the application from its loader:

.. code-block:: javascript

    // assets/admin.js
    import { startStimulusApp } from '@symfony/stimulus-bundle/admin';

    startStimulusApp();

Finally, render this entrypoint in the templates of the area:

.. code-block:: twig

    {# templates/admin/base.html.twig #}
    {{ importmap('admin') }}

The controllers of an application are resolved as follows:

* ``include_global_paths`` (default ``true``) also loads the controllers found
  in the global ``controller_paths``. Set it to ``false`` to load only the
  controllers of the application;
* ``controllers_json`` points to a file with the same format as
  ``assets/controllers.json``. When set, this file replaces the global
  ``controllers_json`` file for the application, so the ``enabled``, ``fetch``
  and ``autoimport`` settings of the global file do not apply to the
  application. When omitted, the application uses the global file. The
  configured file must exist;
* ``merge_controllers_json`` (default ``false``) merges the ``controllers_json``
  file of the application over the global one instead of replacing it. It
  requires the ``controllers_json`` option;
* when two controllers have the same name, a controller from the global
  ``controller_paths`` overrides a UX controller, and a controller from the
  application ``controller_paths`` overrides both.

Eager and lazy controllers, as well as the debug mode, work the same way as
with the global ``@symfony/stimulus-bundle`` loader, which is unchanged.

Load only one Stimulus application per page: do not render the global
``app`` entrypoint and an application entrypoint on the same page.

For example, this ``assets/admin/controllers.json`` file loads only the Chart.js
controller in the admin application:

.. code-block:: json

    {
        "controllers": {
            "@symfony/ux-chartjs": {
                "chart": {
                    "enabled": true,
                    "fetch": "eager"
                }
            }
        }
    }

.. caution::

    Symfony Flex only updates the global ``assets/controllers.json`` file. When
    you install, update or remove a UX package, apply the change to the
    ``controllers_json`` file of each application.

With ``merge_controllers_json: true``, the application file only lists the
settings that differ from the global file:

* a controller that is not listed in the application file keeps its settings
  from the global file, so a controller added to the global file by Symfony
  Flex is also loaded in the application;
* for a listed controller, each setting of the application file (``enabled``,
  ``fetch``, ``name``) overrides the one of the global file, and the other
  settings are kept;
* ``autoimport`` entries are merged by path, and the application file wins;
* to remove a controller of the global file from the application, set
  ``"enabled": false``.

For example, this application file loads the Chart.js controller lazily and
does not load the Autocomplete controller, while all the other controllers of
the global file are loaded unchanged:

.. code-block:: yaml

    # config/packages/stimulus.yaml
    stimulus:
        applications:
            admin:
                loader: '%kernel.project_dir%/assets/admin/stimulus_loader.js'
                controllers_json: '%kernel.project_dir%/assets/admin/controllers.json'
                merge_controllers_json: true

.. code-block:: json

    {
        "controllers": {
            "@symfony/ux-chartjs": {
                "chart": {
                    "fetch": "lazy"
                }
            },
            "@symfony/ux-autocomplete": {
                "autocomplete": {
                    "enabled": false
                }
            }
        }
    }

If your entrypoint used to start the global application and register extra
controllers with ``app.register()``, move those controllers into the
``controller_paths`` of the application and remove the manual calls.

.. note::

    The importmap is not a security boundary. It only decides which modules
    a page imports and preloads: any asset published by AssetMapper, such as
    the controllers of another application, remains publicly reachable.

Scoping Controllers with Reprise
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

``startStimulusApp()`` from ``@symfony/reprise/stimulus`` takes no arguments,
like the AssetMapper one. What it reads is generated by the Reprise plugin,
and that generation is configurable, so each build can be given its own set
of controllers.

If a single config defines several entry points, they all share the same
controller set, because there is only one plugin instance. To scope
controllers to a given area of your application, give that area its own
build config and point the plugin at its own controllers directory. For
example, an admin area could use a dedicated config like this:

.. code-block:: javascript

    // vite.config.admin.ts  -- run with `vite build --config vite.config.admin.ts`
    import { defineConfig } from 'vite'
    import Symfony from '@symfony/reprise/vite'

    export default defineConfig({
      input: {
        admin: './assets/admin.js',
      },
      plugins: [
        Symfony({
          outputPath: 'public/admin-build',
          publicPath: '/admin-build/',
          stimulus: {
            controllersJson: 'assets/admin/controllers.json',
            controllersDir: 'assets/admin/controllers',
          },
        }),
      ],
    })

This entry point then starts the application the usual way:

.. code-block:: javascript

    // assets/admin.js
    import { startStimulusApp } from '@symfony/reprise/stimulus'

    const app = startStimulusApp()

Register this build on the PHP side under ``reprise.builds`` so the Twig tag
functions can address it with a ``build`` argument.

See the `Reprise Stimulus documentation`_ for what else the returned application
lets you do, such as registering controllers that are not declared in
``controllers.json``.

Scoping Controllers with Webpack Encore
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

``startStimulusApp()`` from `@symfony/stimulus-bridge`_ accepts a Webpack
context, so you can point each entry at its own controller directory. This
``assets/admin.js`` file loads only the controllers under
``controllers/admin``:

.. code-block:: javascript

    import { startStimulusApp } from '@symfony/stimulus-bridge';

    export const app = startStimulusApp(require.context(
        '@symfony/stimulus-bridge/lazy-controller-loader!./controllers/admin',
        true,
        /\.[jt]sx?$/
    ));

If you keep the controllers shared between areas in their own directory, you
can load several contexts into the same application:

.. code-block:: javascript

    import { startStimulusApp } from '@symfony/stimulus-bridge';
    import { definitionsFromContext } from '@hotwired/stimulus-webpack-helpers';

    const app = startStimulusApp();

    app.load(definitionsFromContext(require.context('./controllers/common', true)));
    app.load(definitionsFromContext(require.context('./controllers/admin', true)));

This second form drops the lazy controller loader, so add it back to the
context path if you rely on lazy loading.

.. _Reprise vs Encore vs AssetMapper: https://symfony.com/doc/current/frontend.html
.. _Symfony Flex: https://symfony.com/doc/current/setup/flex.html
.. _Stimulus Documentation: https://stimulus.hotwired.dev/
.. _`@symfony/stimulus-bridge`: https://github.com/symfony/stimulus-bridge
.. _`Stimulus`: https://stimulus.hotwired.dev/
.. _`Webpack Encore`: https://symfony.com/doc/current/frontend/encore/index.html
.. _`AssetMapper`: https://symfony.com/doc/current/frontend/asset_mapper.html
.. _`Reprise`: https://github.com/symfony/reprise
.. _`Reprise Stimulus documentation`: https://symfony.com/bundles/reprise/current/index.html#symfony-ux-stimulus-controllers
.. _`Stimulus Controllers & Values`: https://stimulus.hotwired.dev/reference/values
.. _`CSS Classes`: https://stimulus.hotwired.dev/reference/css-classes
.. _`Outlets`: https://stimulus.hotwired.dev/reference/outlets
.. _`Stimulus Actions`: https://stimulus.hotwired.dev/reference/actions
.. _`parameters`: https://stimulus.hotwired.dev/reference/actions#action-parameters
.. _`Stimulus Targets`: https://stimulus.hotwired.dev/reference/targets
.. _`StimulusBundle Flex recipe`: https://github.com/symfony/recipes/tree/main/symfony/stimulus-bundle
.. _`stimulus-use`: https://stimulus-use.github.io/stimulus-use
.. _`stimulus-components`: https://www.stimulus-components.com/
.. _`TypeScript`: https://www.typescriptlang.org/
.. _`sensiolabs/typescript-bundle`: https://github.com/sensiolabs/AssetMapperTypeScriptBundle
.. _`Stimulus plugin`: https://plugins.jetbrains.com/plugin/24562-stimulus
.. _`official UX packages`: https://ux.symfony.com/packages
