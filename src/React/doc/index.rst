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

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-react

The Flex recipe will automatically set things up for you, like adding
``.enableReactPreset()`` to your ``webpack.config.js`` file and adding code
to load your React components inside ``assets/app.js``.

Next, install a package to help React:

.. code-block:: terminal

    $ npm install -D @babel/preset-react --force
    $ npm run watch

That's it! Any files inside ``assets/react/controllers/`` can now be rendered as
React components.

Usage
-----

The Flex recipe will have already added the ``registerReactControllerComponents()``
code to your ``assets/app.js`` file:

.. code-block:: javascript

    // assets/app.js
    import { registerReactControllerComponents } from '@symfony/ux-react';

    registerReactControllerComponents(require.context('./react/controllers', true, /\.(j|t)sx?$/));

This will load all React components located in the ``assets/react/controllers``
directory. These are known as **React controller components**: top-level
components that are meant to be rendered from Twig.

You can render any React controller component in Twig using the ``react_component()``.

For example:

.. code-block:: javascript

    // assets/react/controllers/Hello.jsx
    import React from 'react';

    export default function (props) {
        return <div>Hello {props.fullName}</div>;
    }

.. code-block:: html+twig

    {# templates/home.html.twig #}
    {% extends 'base.html.twig' %}

    {% block body %}
        <div {{ react_component('Hello', { fullName: 'Fabien' }) }}>
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

.. note:: 

    Compile your ``.jsx`` files to pure JavaScript files. This can be done by
   installing Babel and the ``@babel/preset-react`` preset. Example:
   https://github.com/symfony/ux/blob/2.x/ux.symfony.com/package.json

1- Create package.json in your project. This file is not included in the **Standard package**. (code below)

.. code-block:: json-object

 {
   "devDependencies": {
    "@babel/cli": "^7.21.5",
    "@babel/core": "^7.21.8",
    "@babel/preset-react": "^7.18.6",
    "svelte": "^3.59.1"
  },
  "license": "MIT",
  "private": true,
  "scripts": {
    "build": "yarn build-react && yarn build-svelte",
    "build-react": "babel assets/react --out-dir assets/build/react --presets=@babel/preset-react",
    "build-svelte": "node bin/compile_svelte.js"
  }
 }

2- Point this library at the "built" controllers directory that contains the final
   JavaScript files:

.. code-block:: yaml

    # config/packages/asset_mapper.yaml
    react:
        controllers_path: '%kernel.project_dir%/assets/build/react/controllers'

Also, inside of your ``.jsx`` files, when importing another component, use the
``.js`` extension:

.. code-block:: javascript

    // use PackageList.js even though the file is named PackageList.jsx
    import PackageList from '../components/PackageList.js';

3- Install the required Node.js packages

.. code-block:: terminal

    $ npm install

4- The Flex recipe will have already added the registerReactControllerComponents() code to your assets/app.js file:

.. code-block:: javascript

    // assets/app.js
    import { registerReactControllerComponents } from '@symfony/ux-react';

    registerReactControllerComponents();

.. note:: 

Do not use **require.context** parameter. Not working with AssetMapper

5- Finally, run the 'build' command below so that we can use the React components you wrote or installed.

.. code-block:: terminal

    $ yarn build-react

Dont forget run **php bin/console asset-map:compile** command before deploy.

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

.. _`React`: https://reactjs.org/
.. _`the Symfony UX initiative`: https://ux.symfony.com/
