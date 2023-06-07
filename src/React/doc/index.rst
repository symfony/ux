Symfony UX React
================

Symfony UX React is a Symfony bundle integrating `React`_ in
Symfony applications. It is part of `the Symfony UX initiative`_.

React is a JavaScript library for building user interfaces.
Symfony UX React provides tools to render React components from Twig,
handling rendering and data transfers.

Symfony UX React supports React 18+.

Installation
------------

.. note::

    This package works best with WebpackEncore. To use it with AssetMapper, see
    :ref:`Using with AssetMapper <using-with-asset-mapper>`.

Before you start, make sure you have `StimulusBundle configured in your app`_.
Then install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-react

Next, in ``webpack.config.js``, enable React support:

.. code-block:: javascript

    // webpack.config.js
    // ...

    Encore
        // ...
        .enableReactPreset()
    ;

Install a package to help React:

.. code-block:: terminal

    $ npm install -D @babel/preset-react --force
    $ npm run watch

    # or use yarn
    $ yarn add @babel/preset-react --dev --force
    $ yarn watch

Finally, to load your React components, add the following lines to ``assets/app.js``:

.. code-block:: javascript

    // assets/app.js
    import { registerReactControllerComponents } from '@symfony/ux-react';

    // Registers React controller components to allow loading them from Twig
    //
    // React controller components are components that are meant to be rendered
    // from Twig. These component then rely on other components that won't be called
    // directly from Twig.
    //
    // By putting only controller components in `react/controllers`, you ensure that
    // internal components won't be automatically included in your JS built file if
    // they are not necessary.
    registerReactControllerComponents(require.context('./react/controllers', true, /\.(j|t)sx?$/));

That's it! Create an ``assets/react/controllers/`` directory and start creating your
React components.

Usage
-----

UX React works by using a system of **React controller components**: React components that
are registered using ``registerReactControllerComponents`` and that are meant to be rendered
from Twig.

When using the ``registerReactControllerComponents`` configuration shown previously, all
React components located in the directory ``assets/react/controllers`` are registered as
React controller components.

You can then render any React controller component in Twig using the ``react_component``.

For example:

.. code-block:: javascript

    // assets/react/controllers/MyComponent.jsx
    import React from 'react';

    export default function (props) {
        return <div>Hello {props.fullName}</div>;
    }

.. code-block:: html+twig

    {# templates/home.html.twig #}
    {% extends 'base.html.twig' %}

    {% block body %}
        <div {{ react_component('MyComponent', { 'fullName': number }) }}>
            Loading... <i class="fas fa-cog fa-spin fa-3x"></i>
        </div>

        {# Component living in a subdirectory: "assets/react/controllers/Admin/OtherComponent" #}
        <div {{ react_component('Admin/OtherComponent') }}></div>
    {% endblock %}

.. _using-with-asset-mapper:

Using with AssetMapper
----------------------

Because the JSX format isn't pure JavaScript, using this library with AssetMapper
requires some extra steps.

#. Compile your ``.jsx`` files to pure JavaScript files. This can be done by
   installing Babel and the ``@babel/preset-react`` preset. Example:
   https://github.com/symfony/ux/blob/2.x/ux.symfony.com/package.json

#. Point this library at the "built" controllers directory that contains the final
   JavaScript files:

.. code-block:: yaml

    # config/packages/react.yaml
    react:
        controllers_path: '%kernel.project_dir%/assets/build/react/controllers'

Also, inside of your ``.jsx`` files, when importing another component, use the
``.js`` extension:

.. code-block:: javascript

    // use PackageList.js even though the file is named PackageList.jsx
    import PackageList from '../components/PackageList.js';

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

.. _`React`: https://reactjs.org/
.. _`the Symfony UX initiative`: https://symfony.com/ux
.. _StimulusBundle configured in your app: https://symfony.com/bundles/StimulusBundle/current/index.html
