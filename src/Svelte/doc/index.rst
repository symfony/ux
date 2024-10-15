Symfony UX Svelte
=================

Symfony UX Svelte is a Symfony bundle integrating `Svelte`_ in
Symfony applications. It is part of `the Symfony UX initiative`_.

Svelte is a JavaScript framework for building user interfaces.
Symfony UX Svelte provides tools to render Svelte components from Twig,
handling rendering and data transfers.

Symfony UX Svelte supports Svelte 3 and Svelte 4.

Installation
------------

.. note::

    This package works best with WebpackEncore. To use it with AssetMapper, see
    :ref:`Using with AssetMapper <using-with-asset-mapper>`.

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-svelte

    $ npm install --force
    $ npm run watch

The Flex recipe will automatically set things up for you, like adding
``.enableSvelte()`` to your ``webpack.config.js`` file and adding code
to load your Svelte components inside ``assets/app.js``.

Next, install a package to help Svelte:

.. code-block:: terminal

    $ npm install svelte-loader --save-dev

That's it! Any files inside ``assets/svelte/controllers/`` can now be rendered as
Svelte components.

If you are using Svelte 4, you will have to add ``browser``, ``import`` and ``svelte``
to the ``conditionNames`` array. This is necessary as per `the Svelte 4 migration guide`_
for bundlers such as webpack, to ensure that lifecycle callbacks are internally invoked.

To modify the ``conditionNames`` array, append the following changes to the bottom
of your ``webpack.config.js`` file:

.. code-block:: diff

      // webpack.config.js
    - module.exports = Encore.getWebpackConfig();
    + const config = Encore.getWebpackConfig();
    + config.resolve.conditionNames = ['browser', 'import', 'svelte'];
    + module.exports = config;

Usage
-----

The Flex recipe will have already added the ``registerSvelteControllerComponents()``
code to your ``assets/app.js`` file:

.. code-block:: javascript

    // assets/app.js
    import { registerSvelteControllerComponents } from '@symfony/ux-svelte';

    registerSvelteControllerComponents(require.context('./svelte/controllers', true, /\.svelte$/));

This will load all Svelte components located in the ``assets/svelte/controllers``
directory. These are known as **Svelte controller components**: top-level
components that are meant to be rendered from Twig.

You can render any Svelte controller component in Twig using the ``svelte_component()``.

For example:

.. code-block:: javascript

    // assets/svelte/controllers/Hello.svelte
    <script>
        export let name = "Svelte";
    </script>

    <div>Hello {name}</div>


.. code-block:: html+twig

    {# templates/home.html.twig #}
    <div {{ svelte_component('Hello', { 'name': app.user.fullName }) }}></div>

If your Svelte component has a transition that you want to play on initial render, you can use
the third argument ``intro`` of the ``svelte_component()`` function like you would do with the
Svelte client-side component API:

.. code-block:: javascript

    // assets/svelte/controllers/MyAnimatedComponent.svelte
    <script>
        import { fade } from 'svelte/transition';
        export let name = "Svelte";
    </script>

    <div transition:fade|global>Hello {name}</div>


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
.. _`the Symfony UX initiative`: https://ux.symfony.com/
.. _the Svelte 4 migration guide: https://svelte.dev/docs/v4-migration-guide#browser-conditions-for-bundlers
