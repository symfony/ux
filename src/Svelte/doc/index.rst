Symfony UX Svelte
=================

Symfony UX Svelte is a Symfony bundle integrating `Svelte`_ in
Symfony applications. It is part of `the Symfony UX initiative`_.

Svelte is a JavaScript framework for building user interfaces.
Symfony UX Svelte provides tools to render Svelte components from Twig,
handling rendering and data transfers.

Symfony UX Svelte supports Svelte 3 only.

Installation
------------

.. note::

    This package works best with WebpackEncore. To use it with AssetMapper, see
    :ref:`Using with AssetMapper <using-with-asset-mapper>`.

Before you start, make sure you have `StimulusBundle configured in your app`_.

Then install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-svelte

    $ npm install --force
    $ npm run watch

    # or use yarn
    $ yarn install --force
    $ yarn watch

You also need to add the following lines at the end to your ``assets/app.js`` file:

.. code-block:: javascript

    // assets/app.js
    import { registerSvelteControllerComponents } from '@symfony/ux-svelte';

    // Registers Svelte controller components to allow loading them from Twig
    //
    // Svelte controller components are components that are meant to be rendered
    // from Twig. These component can then rely on other components that won't be
    // called directly from Twig.
    //
    // By putting only controller components in `svelte/controllers`, you ensure that
    // internal components won't be automatically included in your JS built file if
    // they are not necessary.
    registerSvelteControllerComponents(require.context('./svelte/controllers', true, /\.svelte$/));

To make sure Svelte components can be loaded by Webpack Encore, you need to add and configure
the `svelte-loader`_ library in your project :

.. code-block:: terminal

    $ npm install svelte svelte-loader --save-dev

    # or use yarn
    $ yarn add svelte svelte-loader --dev

Enable it in your ``webpack.config.js`` file :

.. code-block:: javascript

    // webpack.config.js
    Encore
        // ...
        .enableSvelte()
    ;

Usage
-----

UX Svelte works by using a system of **Svelte controller components**: Svelte components that
are registered using ``registerSvelteControllerComponents()`` and that are meant to be rendered
from Twig.

When using the ``registerSvelteControllerComponents()`` configuration shown previously, all
Svelte components located in the directory ``assets/svelte/controllers`` are registered as
Svelte controller components.

You can then render any Svelte controller component in Twig using the ``svelte_component()`` function:

.. code-block:: javascript

    // assets/svelte/controllers/MyComponent.svelte
    <script>
        export let name = "Svelte";
    </script>

    <div>Hello {name}</div>


.. code-block:: html+twig

    {# templates/home.html.twig #}
    <div {{ svelte_component('MyComponent', { 'name': app.user.fullName }) }}></div>

If your Svelte component has a transition that you want to play on initial render, you can use
the third argument ``intro`` of the ``svelte_component()`` function like you would do with the
Svelte client-side component API:

.. code-block:: javascript

    // assets/svelte/controllers/MyAnimatedComponent.svelte
    <script>
        import { fade } from 'svelte/transition';
        export let name = "Svelte";
    </script>

    <div transition:fade>Hello {name}</div>


.. code-block:: html+twig

    {# templates/home.html.twig #}
    <div {{ svelte_component('MyAnimatedComponent', { 'name': app.user.fullName }, true) }}></div>

.. _using-with-asset-mapper:

Using with AssetMapper
----------------------

Because the ``.svelte`` file format isn't pure JavaScript, using this library with
AssetMapper requires some extra steps.

#. Compile your ``.svelte`` files to pure JavaScript files. This can be done by
   using the ``svelte/compiler`` library, but is a bit of a non-standard process.
   For an example, see https://github.com/symfony/ux/blob/2.x/ux.symfony.com/bin/compile_svelte.js.

#. Point this library at the "built" controllers directory that contains the final
   JavaScript files:

.. code-block:: yaml

    # config/packages/svelte.yaml
    svelte:
        controllers_path: '%kernel.project_dir%/assets/build/svelte/controllers'

Also, inside of your ``.svelte`` files, when importing another component, use the
``.js`` extension:

.. code-block:: javascript

    // use PackageList.js even though the file is named PackageList.svelte
    import PackageList from '../components/PackageList.js';

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

.. _`Svelte`: https://svelte.dev/
.. _`svelte-loader`: https://github.com/sveltejs/svelte-loader/blob/master/README.md
.. _`the Symfony UX initiative`: https://symfony.com/ux
.. _StimulusBundle configured in your app: https://symfony.com/bundles/StimulusBundle/current/index.html
