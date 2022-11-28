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

Before you start, make sure you have `Symfony UX configured in your app`_.

Then install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-react

    # Don't forget to install the JavaScript dependencies as well and compile
    $ npm install --force
    $ npm run watch

    # or use yarn
    $ yarn install --force
    $ yarn watch

You also need to add the following lines at the end to your ``assets/app.js`` file:

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


.. code-block:: twig

    {# templates/home.html.twig #}

    <div {{ react_component('MyComponent', { 'fullName': app.user.fullName }) }}></div>

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

.. _`React`: https://reactjs.org/
.. _`the Symfony UX initiative`: https://symfony.com/ux
.. _`Symfony UX configured in your app`: https://symfony.com/doc/current/frontend/ux.html
