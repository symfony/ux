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

.. note::

    This package works best with WebpackEncore. To use it with AssetMapper, see
    :ref:`Using with AssetMapper <using-with-asset-mapper>`.

Before you start, make sure you have `StimulusBundle configured in your app`_.
Then install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-vue

Next, in ``webpack.config.js``, enable Vue.js support:

.. code-block:: javascript

    // webpack.config.js
    // ...

    Encore
        // ...
        .enableVueLoader()
    ;

Install a package to help Vue:

.. code-block:: terminal

    $ npm install -D vue-loader --force
    $ npm run watch

    # or with yarn
    $ yarn add vue-loader --dev --force
    $ yarn watch

Finally, to load your Vue components, add the following lines to ``assets/app.js``:

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

    // If you prefer to lazy-load your Vue.js controller components, in order to keep the JavaScript bundle the smallest as possible,
    // and improve performance, you can use the following line instead:
    //registerVueControllerComponents(require.context('./vue/controllers', true, /\.vue$/, 'lazy'));

That's it! Create an ``assets/vue/controllers/`` directory and start creating your
Vue components.

Usage
-----

UX Vue.js works by using a system of **Vue.js controller components**: Vue.js components that
are registered using ``registerVueControllerComponents`` and that are meant to be rendered
from Twig.

When using the ``registerVueControllerComponents`` configuration shown previously, all
Vue.js components located in the directory ``assets/vue/controllers`` are registered as
Vue.js controller components.

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

.. code-block:: html+twig

    {# templates/home.html.twig #}
    <div {{ vue_component('MyComponent', { 'name': app.user.fullName }) }}></div>

Events
~~~~~~

The event ``vue:before-mount`` is called before a component is mounted on the page. This is the event to listen if you need to modifiy the Vue application (e.g.: add plugins, add global directives, states ...):

.. code-block:: javascript

    // assets/app.js
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

.. code-block:: javascript

    document.addEventListener('vue:mount', (event) => {
        const {
            componentName, // The Vue component's name
            component, // The resolved Vue component
            props, // The props that are injected to the component
        } = event.detail;
    });

The event ``vue:unmount`` is called when a component has been unmounted on the page:

.. code-block:: javascript

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

.. code-block::

    #Route('/survey/{path<.+>}')
    public function survey($path = ''): Response
    {
        // render the template
    }

This controller will catch any URL that starts with `/survey`. This prefix can then be
used for all the Vue routes:

.. code-block:: javascript

    const router = VueRouter.createRouter({
        history: VueRouter.createWebHistory(),
        routes: [
            { path: '/survey/list', component: ListSurveys },
            { path: '/survey/create', component: CreateSurvey },
            { path: '/survey/edit/:surveyId', component: EditSurvey },
        ],
    });

    app.use(router);

.. _using-with-asset-mapper:

Using with AssetMapper
----------------------

The Vue single-file component (i.e. ``.vue``) file format is not pure JavaScript
and cannot currently be converted to pure JavaScript outside of a bundler like
Webpack Encore or Vite. This means that the ``.vue`` file format cannot be used
with AssetMapper.

If you *do* still want to use Vue with AssetMapper, you can do so by avoiding
the ``.vue`` file format. For example, https://github.com/symfony/ux/blob/2.x/ux.symfony.com/assets/vue/controllers/PackageSearch.js.

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

.. _`Vue.js`: https://vuejs.org/
.. _`the Symfony UX initiative`: https://symfony.com/ux
.. _ `the related section of the documentation`: https://symfony.com/doc/current/frontend/encore/vuejs.html
.. _StimulusBundle configured in your app: https://symfony.com/bundles/StimulusBundle/current/index.html
