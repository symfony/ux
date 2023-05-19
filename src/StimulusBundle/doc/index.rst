StimulusBundle: Symfony integration with Stimulus
=================================================

**EXPERIMENTAL** This bundle is currently experimental. It is possible that
backwards-compatibility breaks could happen between minor versions.

This bundle adds integration between Symfony, `Stimulus`_ and Symfony UX:

A) Twig `stimulus_*` functions & filters to add Stimulus controllers, actions & targets in your templates;
B) Integration with Symfony UX & AssetMapper;
C) A helper service to build the Stimulus data attributes and use them in your services.

Installation
------------

First, if you don't have one yet, choose and install an asset handling system;
both work great with StimulusBundle:

* A) `Webpack Encore`_ Node-based packaging system:

  .. code-block:: terminal

      $ composer require symfony/webpack-encore-bundle
or

* B) `AssetMapper`_: PHP-based system for handling assets:

  .. code-block:: terminal

      $ composer require symfony/asset-mapper

Then, install the bundle:

.. code-block:: terminal

    $ composer require symfony/stimulus-bundle

If you're using `Symfony Flex`_, you're done! The recipe will update the
necessary files. If not, or you're curious, see :ref:`Manual Setup <manual-installation>`.

Usage
-----

You can now create custom Stimulus controllers inside of the ``assets/controllers.``
directory. In fact, you should have an example controller there already: ``hello_controller.js``.

Use the Twig functions from this bundle to activate your controllers:

.. code-block:: html+twig

    <div {{ stimulus_controller('hello') }}>
        ...
    </div>

Your app will also activate any 3rd party controllers (installed by UX bundles)
mentioned in your ``assets/controllers.json`` file.

For a *ton* more details, see the `Symfony UX documentation`_.

Stimulus Twig Helpers
---------------------

stimulus_controller
~~~~~~~~~~~~~~~~~~~

This bundle ships with a special ``stimulus_controller()`` Twig function
that can be used to render `Stimulus Controllers & Values`_ and `CSS Classes`_.

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

Any non-scalar values (like ``data: [1, 2, 3, 4]``) are JSON-encoded. And all
values are properly escaped (the string ``&#x5B;`` is an escaped
``[`` character, so the attribute is really ``[1,2,3,4]``).

If you have multiple controllers on the same element, you can chain them as there's also a ``stimulus_controller`` filter:

.. code-block:: html+twig

    <div {{ stimulus_controller('chart', { 'name': 'Likes' })|stimulus_controller('other-controller') }}>
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

If you have multiple actions and/or methods on the same element, you can chain them as there's also a
``stimulus_action`` filter:

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

If you have multiple targets on the same element, you can chain them as there's also a `stimulus_target` filter:

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

Configuration
-------------

If you're using `AssetMapper`_, you can configure the path to your controllers
directory and the ``controllers.json`` file if you need to use different paths:

.. code-block:: yaml

    # config/packages/stimulus.yaml
    stimulus:
        # the default values
        controller_paths:
            - %kernel.project_dir%/assets/controllers
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

.. _`Stimulus`: https://stimulus.hotwired.dev/
.. _`Webpack Encore`: https://symfony.com/doc/current/frontend.html
.. _`AssetMapper`: https://symfony.com/doc/current/frontend/asset-mapper.html
.. _`Symfony UX documentation`: https://symfony.com/doc/current/frontend/ux.html
.. _`Stimulus Controllers & Values`: https://stimulus.hotwired.dev/reference/values
.. _`CSS Classes`: https://stimulus.hotwired.dev/reference/css-classes
.. _`Stimulus Actions`: https://stimulus.hotwired.dev/reference/actions
.. _`parameters`: https://stimulus.hotwired.dev/reference/actions#action-parameters
.. _`Stimulus Targets`: https://stimulus.hotwired.dev/reference/targets
.. _`StimulusBundle Flex recipe`: https://github.com/symfony/recipes/tree/main/symfony/stimulus-bundle
