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

Next, in ``webpack.config.js``, enable Vue.js support:

.. code-block:: javascript

    // webpack.config.js
    // ...

    Encore
        // ...
        .enableVueLoader()
    ;

Install a package to help Vue:

With NPM:

.. code-block:: terminal

    $ npm install -D vue-loader --force
    $ npm run watch
    
With Yarn:

.. code-block:: terminal

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

That's it! Create an `assets/vue/controllers/` directory and start creating your
Vue components.

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

Typescript
----------

To enable Typescript support for Vue enable the Typescript loader:

.. code-block:: javascript

    // webpack.config.js
    // uncomment if you use TypeScript
    .enableTypeScriptLoader()

It's ofen useful to add aliases for your components:

.. code-block:: javascript

    // webpack.config.js
    .addAliases({
      '@': path.resolve('assets/vue/')
    })

Typescript needs to know about the `.vue` extension which is done through a
`shims-vue.d.ts` file. For Vue 3 use the following:

.. code-block:: typescript

    // assets/shims-vue.d.ts
    declare module '*.vue';


For Vue 2 use:

.. code-block:: typescript

    // assets/shims-vue.d.ts
    declare module "*.vue" {
        import Vue from "vue";
        export default Vue;
    }

Config
~~~~~~

Install `@vue/tsconfig`:

.. code-block:: terminal

    $ npm install -D @vue/tsconfig

Normally we would extend this config in `tsconfig.json`:

.. code-block:: json

    // tsconfig.json
    {
        "extends": "@vue/tsconfig/tsconfig.dom.json"
    }

However, Encore is currently using Typescript 4, which will result in the following error when
running `npm run watch`, as `bundler` cannot be used for `moduleResolution`:

.. code-block:: text

    [tsl] ERROR in node_modules/@vue/tsconfig/tsconfig.json(12,25)
        TS6046: Argument for '--moduleResolution' option must be: 'node', 'classic', 'node16', 'nodenext'.

Instead, copy the contents of `@vue/tsconfig/tsconfig.json` into `tsconfig.json` and change `moduleResolution`
to `nodenext`, and adding in some additional configuration for `baseUrl`, `paths`, `include`, and `exclude`:

.. code-block:: json

    // tsconfig.json
    {
        // "extends": "@vue/tsconfig/tsconfig.dom.json",
        "compilerOptions": {
            // As long as you are using a build tool, we recommend you to author and ship in ES modules.
            // Even if you are targeting Node.js, because
            //  - `CommonJS` is too outdated
            //  - the ecosystem hasn't fully caught up with `Node16`/`NodeNext`
            // This recommendation includes environments like Vitest, Vite Config File, Vite SSR, etc.
            "module": "ESNext",

            // We expect users to use bundlers.
            // So here we enable some resolution features that are only available in bundlers.
            "moduleResolution": "nodenext",
            "resolveJsonModule": true,
            // `allowImportingTsExtensions` can only be used when `noEmit` or `emitDeclarationOnly` is set.
            // But `noEmit` may cause problems with solution-style tsconfigs:
            // <https://github.com/microsoft/TypeScript/issues/49844>
            // And `emitDeclarationOnly` is not always wanted.
            // Considering it's not likely to be commonly used in Vue codebases, we don't enable it here.

            // Required in Vue projects
            "jsx": "preserve",
            "jsxImportSource": "vue",

            // `"noImplicitThis": true` is part of `strict`
            // Added again here in case some users decide to disable `strict`.
            // This enables stricter inference for data properties on `this`.
            "noImplicitThis": true,
            "strict": true,

            // <https://devblogs.microsoft.com/typescript/announcing-typescript-5-0/#verbatimmodulesyntax>
            // Any imports or exports without a type modifier are left around. This is important for `<script setup>`.
            // Anything that uses the type modifier is dropped entirely.
            // "verbatimModuleSyntax": true,

            // A few notes:
            // - Vue 3 supports ES2016+
            // - For Vite, the actual compilation target is determined by the
            //   `build.target` option in the Vite config.
            //   So don't change the `target` field here. It has to be
            //   at least `ES2020` for dynamic `import()`s and `import.meta` to work correctly.
            // - If you are not using Vite, feel free to overwrite the `target` field.
            "target": "ESNext",
            // For spec compilance.
            // `true` by default if the `target` is `ES2020` or higher.
            // Explicitly set it to `true` here in case some users want to overwrite the `target`.
            "useDefineForClassFields": true,

            // Recommended
            "esModuleInterop": true,
            "forceConsistentCasingInFileNames": true,
            // See <https://github.com/vuejs/vue-cli/pull/5688>
            "skipLibCheck": true,

            // Project overrides
            "baseUrl": "./",
            // These are just here for IDE path mapping - for compilation we use addAliases() in webpack.config.js
            "paths": {
                "@/*": [
                    "./assets/vue/*"
                ],
            }
        },
        "include": [
            "./assets/vue/**/*.ts",
            "./assets/vue/**/*.vue",
        ],
        "exclude": [
            "./node_modules",
            "./vendor"
        ]
    }

Typescript is now configured to work with Vue.

Vue components using Typescript
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Create Vue components which use Typescript:

.. code-block:: javascript

    // assets/vue/controllers/MyTypescriptComponent.vue
    <template>
        <div>
            <demo :name="name"></demo>
        </div>
    </template>

    <script lang="ts" setup>
        import Demo from "@/components/DemoComponent.vue";

        defineProps({
            name: String
        });
    </script>

    // assets/vue/components/DemoComponent.vue
    <template>
        <div>Hello {{ name }}!</div>
    </template>

    <script lang="ts" setup>
        defineProps({
            name: String
        });
    </script>


These components can then be rendered in Twig as follows:

.. code-block:: html+twig

    {# templates/home.html.twig #}
    <div {{ vue_component('MyTypescriptComponent', { 'name': app.user.fullName }) }}></div>


Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

.. _`Vue.js`: https://vuejs.org/
.. _`the Symfony UX initiative`: https://symfony.com/ux
.. _ `the related section of the documentation`: https://symfony.com/doc/current/frontend/encore/vuejs.html
.. _`Symfony UX configured in your app`: https://symfony.com/doc/current/frontend/ux.html
