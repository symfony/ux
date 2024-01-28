StimulusBundle: Symfony integration with Stimulus
=================================================

.. tip::

    Check out live demos of Symfony UX at https://ux.symfony.com!

This bundle adds integration between Symfony, `Stimulus`_ and the Symfony UX packages:

A) Twig ``stimulus_`` functions & filters to add Stimulus controllers,
   actions & targets in your templates;

B) Integration to load :ref:`UX Packages <ux-packages>` (extra Stimulus controllers)

Installation
------------

First, if you don't have one yet, choose and install an asset handling system;
both work great with StimulusBundle:

* A) `Webpack Encore`_ Node-based packaging system:

or

* B) `AssetMapper`_: PHP-based system for handling assets:

See `Encore vs AssetMapper`_ to learn which is best for your project.

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
`sensiolabs_typescript.source_dir` configuration. Finally, create your controller in that
directory and you're good to go.

.. _ux-packages:

The UX Packages
~~~~~~~~~~~~~~~

Symfony provides a set of UX packages that add extra Stimulus controllers to solve
common problems. StimulusBundle activates any 3rd party Stimulus controllers
that are mentioned in your ``assets/controllers.json`` file. This file is updated
whenever you install a UX package.

The official UX packages are:

* `ux-autocomplete`_: Transform ``EntityType``, ``ChoiceType`` or *any*
  ``<select>`` element into an Ajax-powered autocomplete field
  (`see demo <https://ux.symfony.com/autocomplete>`_)
* `ux-chartjs`_: Easy charts with `Chart.js`_ (`see demo <https://ux.symfony.com/chartjs>`_)
* `ux-cropperjs`_: Form Type and tools for cropping images (`see demo <https://ux.symfony.com/cropperjs>`_)
* `ux-dropzone`_: Form Type for stylized "drop zone" for file uploads
  (`see demo <https://ux.symfony.com/dropzone>`_)
* `ux-lazy-image`_: Optimize Image Loading with BlurHash
  (`see demo <https://ux.symfony.com/lazy-image>`_)
* `ux-live-component`_: Build Dynamic Interfaces with Zero JavaScript
  (`see demo <https://ux.symfony.com/live-component>`_)
* `ux-notify`_: Send server-sent native notification with Mercure
  (`see demo <https://ux.symfony.com/notify>`_)
* `ux-react`_: Render `React`_ component from Twig (`see demo <https://ux.symfony.com/react>`_)
* `ux-svelte`_: Render `Svelte`_ component from Twig (`see demo <https://ux.symfony.com/svelte>`_)
* `ux-swup`_: Integration with `Swup`_ (`see demo <https://ux.symfony.com/swup>`_)
* `ux-toggle-password`_: Toggle visibility of password inputs
  (`see demo <https://ux.symfony.com/toggle-password>`_)
* `ux-translator`_: Use your Symfony translations in JavaScript `Swup`_ (`see demo <https://ux.symfony.com/translator>`_)
* `ux-turbo`_: Integration with `Turbo Drive`_ for a single-page-app experience
  (`see demo <https://ux.symfony.com/turbo>`_)
* `ux-twig-component`_: Build Twig Components Backed by a PHP Class
  (`see demo <https://ux.symfony.com/twig-component>`_)
* `ux-typed`_: Integration with `Typed`_ (`see demo <https://ux.symfony.com/typed>`_)
* `ux-vue`_: Render `Vue`_ component from Twig (`see demo <https://ux.symfony.com/vue>`_)

Lazy Stimulus Controllers
~~~~~~~~~~~~~~~~~~~~~~~~~

By default, all of your controllers (i.e. files in ``assets/controllers/`` +
controllers in ``assets/controllers.json``) will be downloaded and loaded on
every page.

Sometimes you may have a controller that's only used on some pages. In that case,
you can make the controller "lazy". In this case, will *not be downloaded on
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

    If you write your controllers using TypeScript, make sure
    ``removeComments`` is not set to ``true`` in your TypeScript config.

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

This bundle adds 3 Twig functions/filters to help add Stimulus controllers,
actions & targets in your templates.

.. note::

    Though this bundle provides these helpful Twig functions/filters, it's
    recommended to use raw data attributes instead, as they're straightforward.

.. tip::

    If you use PhpStorm IDE - you may want to install
    [Stimulus plugin](https://plugins.jetbrains.com/plugin/18940-stimulus)
    to get nice auto-completion for the attributes.

stimulus_controller
~~~~~~~~~~~~~~~~~~~

This bundle ships with a special ``stimulus_controller()`` Twig function
that can be used to render `Stimulus Controllers & Values`_ and `CSS Classes`_.
Stimulus Controllers can also reference other controllers by using `Outlets`_.

For example:

.. code-block:: html+twig

    <div {{ stimulus_controller('chart', { 'name': 'Likes', 'data': [1, 2, 3, 4] }) }}>
        Hello
    </div>

    <!-- would render -->
    <div
       data-controller="chart"
       data-chart-name-value="Likes"
       data-chart-data-value="&#x5B;1,2,3,4&#x5D;"
    >
       Hello
    </div>

If you want to set CSS classes:

.. code-block:: html+twig

    <div {{ stimulus_controller('chart', { 'name': 'Likes', 'data': [1, 2, 3, 4] }, { 'loading': 'spinner' }) }}>
        Hello
    </div>

    <!-- would render -->
    <div
       data-controller="chart"
       data-chart-name-value="Likes"
       data-chart-data-value="&#x5B;1,2,3,4&#x5D;"
       data-chart-loading-class="spinner"
    >
       Hello
    </div>

    <!-- or without values -->
    <div {{ stimulus_controller('chart', controllerClasses = { 'loading': 'spinner' }) }}>
        Hello
    </div>

And with outlets:

.. code-block:: html+twig

    <div {{ stimulus_controller('chart', { 'name': 'Likes', 'data': [1, 2, 3, 4] }, { 'loading': 'spinner' }, { 'other': '.target' ) }}>
        Hello
    </div>

    <!-- would render -->
    <div
       data-controller="chart"
       data-chart-name-value="Likes"
       data-chart-data-value="&#x5B;1,2,3,4&#x5D;"
       data-chart-loading-class="spinner"
       data-chart-other-outlet=".target"
    >
       Hello
    </div>

    <!-- or without values/classes -->
    <div {{ stimulus_controller('chart', controllerOutlets = { 'other': '.target' }) }}>
        Hello
    </div>

Any non-scalar values (like ``data: [1, 2, 3, 4]``) are JSON-encoded. And all
values are properly escaped (the string ``&#x5B;`` is an escaped
``[`` character, so the attribute is really ``[1,2,3,4]``).

If you have multiple controllers on the same element, you can chain them as
there's also a ``stimulus_controller`` filter:

.. code-block:: html+twig

    <div {{ stimulus_controller('chart', { 'name': 'Likes' })|stimulus_controller('other-controller') }}>
        Hello
    </div>

    <!-- would render -->
    <div data-controller="chart other-controller" data-chart-name-value="Likes">
        Hello
    </div>

You can also retrieve the generated attributes as an array, which can be helpful e.g. for forms:

.. code-block:: twig

    {{ form_start(form, { attr: stimulus_controller('chart', { 'name': 'Likes' }).toArray() }) }}

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

    <div {{ stimulus_target('controller', 'a-target') }}>Hello</div>
    <div {{ stimulus_target('controller', 'a-target second-target') }}>Hello</div>

    <!-- would render -->
    <div data-controller-target="a-target">Hello</div>
    <div data-controller-target="a-target second-target">Hello</div>

If you have multiple targets on the same element, you can chain them as there's
also a ``stimulus_target`` filter:

.. code-block:: html+twig

    <div {{ stimulus_target('controller', 'a-target')|stimulus_target('other-controller', 'another-target') }}>
        Hello
    </div>

    <!-- would render -->
    <div data-controller-target="a-target" data-other-controller-target="another-target">
        Hello
    </div>

You can also retrieve the generated attributes as an array, which can be helpful e.g. for forms:

.. code-block:: twig

    {{ form_row(form.password, { attr: stimulus_target('hello-controller', 'a-target').toArray() }) }}

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

.. _manual-installation:

Manual Installation Details
---------------------------

When you install this bundle, its Flex recipe should handle updating all the files
needed. If you're not using Flex or want to double-check the changes, check out
the `StimulusBundle Flex recipe`_. Here's a summary of what's inside:

* ``assets/bootstrap.js`` starts the Stimulus application and loads your
  controllers. It's imported by ``assets/app.js`` and its exact content
  depends on whether you have Webpack Encore or AssetMapper installed
  (see below).

* ``assets/app.js`` is *updated* to import ``assets/bootstrap.js``

* ``assets/controllers.json`` This file starts (mostly) empty and is automatically
  updated as your install UX packages that provide Stimulus controllers.

* ``assets/controllers/`` This directory is where you should put your custom Stimulus
  controllers. It comes with one example ``hello_controller.js`` file.

A few other changes depend on which asset system you're using:

With WebpackEncoreBundle
~~~~~~~~~~~~~~~~~~~~~~~~

If you're using Webpack Encore, the recipe will also update your ``webpack.config.js``
file to include this line:

.. code-block:: javascript

    // webpack.config.js
    .enableStimulusBridge('./assets/controllers.json')

The ``assets/bootstrap.js`` file will be updated to look like this:

.. code-block:: javascript

    // assets/bootstrap.js
    import { startStimulusApp } from '@symfony/stimulus-bridge';

    // Registers Stimulus controllers from controllers.json and in the controllers/ directory
    export const app = startStimulusApp(require.context(
        '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
        true,
        /\.[jt]sx?$/
    ));

And 2 new packages - ``@hotwired/stimulus`` and ``@symfony/stimulus-bridge`` - will
be added to your ``package.json`` file.

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
            'url' => 'https://ga.jspm.io/npm:@hotwired/stimulus@3.2.1/dist/stimulus.js',
        ],
    ];

The recipe will update your ``assets/bootstrap.js`` file to look like this:

.. code-block:: javascript

    // assets/bootstrap.js
    import { startStimulusApp } from '@symfony/stimulus-bundle';

    const app = startStimulusApp();

The ``@symfony/stimulus-bundle`` refers the one of the new entries in your
``importmap.php`` file. This file is dynamically built by the bundle and
will import all your custom controllers as well as those from ``controllers.json``.
It will also dynamically enable "debug" mode in Stimulus when your application
is running in debug mode.

.. tip::

    For AssetMapper 6.3 only, you also need a ``{{ ux_controller_link_tags() }``
    in ``base.html.twig``. This is not needed in AssetMapper 6.4+.

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

Finally, your ``assets/bootstrap.js`` file will automatically register:

* All files in ``assets/controllers/`` as Stimulus controllers;
* And all controllers described in ``assets/controllers.json`` as Stimulus controllers.

.. note::

    If you're using WebpackEncore, the ``bootstrap.js`` file works in partnership
    with `@symfony/stimulus-bridge`_. With AssetMapper, the ``bootstrap.js`` file
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

.. _Encore vs AssetMapper: https://symfony.com/doc/current/frontend.html
.. _Symfony Flex: https://symfony.com/doc/current/setup/flex.html
.. _Stimulus Documentation: https://stimulus.hotwired.dev/
.. _`@symfony/stimulus-bridge`: https://github.com/symfony/stimulus-bridge
.. _`Stimulus`: https://stimulus.hotwired.dev/
.. _`Webpack Encore`: https://symfony.com/doc/current/frontend.html
.. _`AssetMapper`: https://symfony.com/doc/current/frontend/asset_mapper.html
.. _`Stimulus Controllers & Values`: https://stimulus.hotwired.dev/reference/values
.. _`CSS Classes`: https://stimulus.hotwired.dev/reference/css-classes
.. _`Outlets`: https://stimulus.hotwired.dev/reference/outlets
.. _`Stimulus Actions`: https://stimulus.hotwired.dev/reference/actions
.. _`parameters`: https://stimulus.hotwired.dev/reference/actions#action-parameters
.. _`Stimulus Targets`: https://stimulus.hotwired.dev/reference/targets
.. _`StimulusBundle Flex recipe`: https://github.com/symfony/recipes/tree/main/symfony/stimulus-bundle
.. _`ux-autocomplete`: https://symfony.com/bundles/ux-autocomplete/current/index.html
.. _`ux-chartjs`: https://symfony.com/bundles/ux-chartjs/current/index.html
.. _`ux-cropperjs`: https://symfony.com/bundles/ux-cropperjs/current/index.html
.. _`ux-dropzone`: https://symfony.com/bundles/ux-dropzone/current/index.html
.. _`ux-lazy-image`: https://symfony.com/bundles/ux-lazy-image/current/index.html
.. _`ux-live-component`: https://symfony.com/bundles/ux-live-component/current/index.html
.. _`ux-notify`: https://symfony.com/bundles/ux-notify/current/index.html
.. _`ux-react`: https://symfony.com/bundles/ux-react/current/index.html
.. _ux-translator: https://symfony.com/bundles/ux-translator/current/index.html
.. _`ux-swup`: https://symfony.com/bundles/ux-swup/current/index.html
.. _`ux-toggle-password`: https://symfony.com/bundles/ux-toggle-password/current/index.html
.. _`ux-turbo`: https://symfony.com/bundles/ux-turbo/current/index.html
.. _`ux-twig-component`: https://symfony.com/bundles/ux-twig-component/current/index.html
.. _`ux-typed`: https://symfony.com/bundles/ux-typed/current/index.html
.. _`ux-vue`: https://symfony.com/bundles/ux-vue/current/index.html
.. _`ux-svelte`: https://symfony.com/bundles/ux-svelte/current/index.html
.. _`Chart.js`: https://www.chartjs.org/
.. _`Swup`: https://swup.js.org/
.. _`React`: https://reactjs.org/
.. _`Svelte`: https://svelte.dev/
.. _`Turbo Drive`: https://turbo.hotwired.dev/
.. _`Typed`: https://github.com/mattboldt/typed.js/
.. _`Vue`: https://vuejs.org/
.. _`stimulus-use`: https://stimulus-use.github.io/stimulus-use
.. _`stimulus-components`: https://stimulus-components.netlify.app/
.. _`TypeScript`: https://www.typescriptlang.org/
.. _`sensiolabs/typescript-bundle`: https://github.com/sensiolabs/AssetMapperTypeScriptBundle
