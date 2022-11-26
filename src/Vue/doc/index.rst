Symfony UX Vue.js
=================

Symfony UX Vue.js is a Symfony bundle integrating `Vue.js`_ in
Symfony applications. It is part of `the Symfony UX initiative`_.

Vue.js is a JavaScript framework for building user interfaces.
Symfony UX Vue.js provides tools to render Vue components from Twig,
handling rendering and data transfers.

Symfony UX Vue.js supports Vue.js v3 only.

Installation
------------

Before you start, make sure you have `Symfony UX configured in your app`_.

Then install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-vue

    # Don't forget to install the JavaScript dependencies as well and compile
    $ npm install --force
    $ npm run watch

    # or use yarn
    $ yarn install --force
    $ yarn watch

You also need to add the following lines at the end to your ``assets/app.js`` file:

.. code-block:: javascript

    // assets/app.js
    import { registerVueControllerComponents } from '@symfony/ux-vue';

    // Registers Vue.js controller components to allow loading them from Twig
    //
    // Vue.js controller components are components that are meant to be rendered
    // from Twig. These component can then rely on other components that won't be
    // called directly from Twig.
    //
    // By putting only controller components in `vue/controllers`, you ensure that
    // internal components won't be automatically included in your JS built file if
    // they are not necessary.
    registerVueControllerComponents(require.context('./vue/controllers', true, /\.vue$/));

    // If you prefer to lazy-load your Vue.js controller components, in order to reduce to keep the JavaScript bundle the smallest as possible,
    // and improve performances, you can use the following line instead:
    //registerVueControllerComponents(require.context('./vue/controllers', true, /\.vue$/, 'lazy'));


Usage
-----

UX Vue.js works by using a system of **Vue.js controller components**: Vue.js components that
are registered using ``registerVueControllerComponents`` and that are meant to be rendered
from Twig.

When using the ``registerVueControllerComponents`` configuration shown previously, all
Vue.js components located in the directory ``assets/vue/controllers`` are registered as
Vue.js controller components.

To make sure those components can be loaded by Webpack Encore, you need to configure
it by following the instructions in `the related section of the documentation`_.

You can then render any Vue.js controller component in Twig using the ``vue_component``.
For example:

.. code-block:: javascript

    // assets/vue/controllers/MyComponent.vue
    <template>
        <div>Hello {{ name }}!</div>
    </template>

    <script setup>
        defineProps({
            name: String
        });
    </script>

.. code-block:: twig

    {# templates/home.html.twig #}

    <div {{ vue_component('MyComponent', { 'name': app.user.fullName }) }}></div>

Events
~~~~~~

The event ``vue:before-mount`` is called before a component is mounted on the page. This is the event to listen if you need to modifiy the Vue application (e.g.: add plugins, add global directives, ...):

.. code-block:: js

    document.addEventListener('vue:before-mount', (event) => {
        const {
            componentName, // The Vue component's name
            component, // The resolved Vue component
            props, // The props that will be injected to the component
            app, // The Vue application instance
        } = event.detail;

        // Example with Vue Router
        const router = VueRouter.createRouter({
            history: VueRouter.createWebHashHistory(),
            routes: [
                /* ... */
            ],
        });

        app.use(router);
    });

.. note::
   When using Vue Router, you can use "hash" or "memory" history mode
   to prevent your Vue routes from being served through Symfony controllers.
   If you want to use web history mode, see :ref:`Web History mode with Vue Router`

The event ``vue:mount`` is called when a component has been mounted on the page:

.. code-block:: js

    document.addEventListener('vue:mount', (event) => {
        const {
            componentName, // The Vue component's name
            component, // The resolved Vue component
            props, // The props that are injected to the component
        } = event.detail;
    });

The event ``vue:unmount`` is called when a component has been unmounted on the page:

.. code-block:: js

    document.addEventListener('vue:unmount', (event) => {
        const {
            componentName, // The Vue component's name
            props, // The props that were injected to the component
        } = event.detail;
    });

Web History mode with Vue Router
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

To use "web" history mode with Vue Router, a catch-all route will be needed
which should render the same template and Vue component:

.. code-block:: php

    #Route('/survey/{path<.+>}')
    public function survey($path = ''): Response
    {
        // render the template
    }
    
This controller will catch any URL that starts with `/survey`. This prefix can then be
used for all the Vue routes:

.. code-block:: js

    const router = VueRouter.createRouter({
        history: VueRouter.createWebHistory(),
        routes: [
            { path: '/survey/list', component: ListSurveys },
            { path: '/survey/create', component: CreateSurvey },
            { path: '/survey/edit/:surveyId', component: EditSurvey },
        ],
    });

    app.use(router);

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

However it is currently considered `experimental`_,
meaning it is not bound to Symfony's BC policy for the moment.

.. _`Vue.js`: https://vuejs.org/
.. _`the Symfony UX initiative`: https://symfony.com/ux
.. _ `the related section of the documentation`: https://symfony.com/doc/current/frontend/encore/vuejs.html
.. _`experimental`: https://symfony.com/doc/current/contributing/code/experimental.html
.. _`Symfony UX configured in your app`: https://symfony.com/doc/current/frontend/ux.html
