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

Before you start, make sure you have `Symfony UX configured in your app`_.

Then install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-svelte

    # Don't forget to install the JavaScript dependencies as well and compile
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

    $ npm install svelte-loader@^3.0.0 --save-dev

    # or use yarn
    $ yarn add svelte-loader@^3.0.0 --dev

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


.. code-block:: twig

    {# templates/home.html.twig #}

    <div {{ svelte_component('MyComponent', { 'name': app.user.fullName }) }}></div>

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

.. _`Svelte`: https://svelte.dev/
.. _`svelte-loader`: https://github.com/sveltejs/svelte-loader/blob/master/README.md
.. _`the Symfony UX initiative`: https://symfony.com/ux
.. _`Symfony UX configured in your app`: https://symfony.com/doc/current/frontend/ux.html
