Symfony UX Router
=================

**EXPERIMENTAL** This component is currently experimental and is likely to
change, or even change drastically.

Symfony UX Router is a Symfony bundle that generates the URLs of your
`Symfony routes`_ in JavaScript, with the same result as the ``path()`` and
``url()`` Twig functions. With TypeScript, route names and parameters are
type-checked. It is part of `the Symfony UX initiative`_.

Installation
------------

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-router

After you install the bundle, the Symfony Flex recipe creates the following
file:

.. code-block:: javascript

    // assets/router.js
    import { createRouter } from '@symfony/ux-router';
    import { routes, defaultLocale } from '../var/routes/index.js';

    export const { path, url } = createRouter({ routes, defaultLocale });

If you use TypeScript, rename it to ``router.ts`` to type-check route names
and parameters.

Using with AssetMapper
~~~~~~~~~~~~~~~~~~~~~~

When installing with `AssetMapper`_, Flex will add a new item to your
``importmap.php`` file::

    '@symfony/ux-router' => [
        'path' => './vendor/symfony/ux-router/assets/dist/router_controller.js',
    ],

You have nothing else to configure: the bundle registers the dump directory
and the ``@symfony/ux-router`` package in AssetMapper.

Using with Reprise
~~~~~~~~~~~~~~~~~~

If you're using `Symfony Reprise`_ (`Vite`_ or `Rsbuild`_), install your
assets and restart your dev server:

.. code-block:: terminal

    $ npm install --force
    $ npm run dev

The Reprise plugin needs no extra option: your bundler resolves the
``../var/routes/index.js`` import of ``assets/router.js`` like any other file.

Using with Webpack Encore
~~~~~~~~~~~~~~~~~~~~~~~~~

If you're using `Webpack Encore`_, install your assets and restart Encore:

.. code-block:: terminal

    $ npm install --force
    $ npm run watch

.. note::

    For more complex installation scenarios, you can install the JavaScript
    assets through the `@symfony/ux-router npm package`_.

Usage
-----

When warming up the Symfony cache, the routes you expose are dumped as
JavaScript into the ``var/routes/`` directory. TypeScript types are dumped
next to them. In the ``dev`` environment, they are dumped again on the next
request after a route changes.

Exposing routes
~~~~~~~~~~~~~~~

No route is exposed by default. A route is dumped to JavaScript when its name
matches one of the ``routes`` patterns:

.. code-block:: yaml

    # config/packages/ux_router.yaml
    ux_router:
        routes:
            - 'app_*'
            - '!app_admin_*'

Patterns support the ``*`` wildcard, and a leading ``!`` excludes the matching
routes. The ``*`` pattern alone exposes every route, including internal ones
like the profiler's.

A localized route is exposed when its own name (``app_about``) or the name of
one of its variants (``app_about.en``) matches a pattern.

You can also expose (or hide) a single route with its ``expose`` option, which
always wins over the patterns::

    #[Route('/search', name: 'search', options: ['expose' => true])]
    public function search(): Response

.. caution::

    Exposed routes are readable by anyone loading your JavaScript. Do not
    expose routes whose existence must stay secret.

Manually warming the routes cache
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Routes are automatically dumped when warming up the Symfony cache. If you only
need to refresh the JavaScript routes without warming up the whole cache, run:

.. code-block:: terminal

    $ php bin/console ux:router:warm-cache

Disabling TypeScript types dump
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

By default, TypeScript types are dumped next to the JavaScript routes. They
give you autocompletion and type-checking when calling ``path()`` and
``url()``.

AssetMapper ignores them, so you can stop dumping them in production:

.. code-block:: yaml

    # config/packages/ux_router.yaml
    when@prod:
        ux_router:
            dump_typescript: false

Keep them if you build your assets with Reprise or Webpack Encore and
TypeScript: the build needs them to type-check your code.

Generating URLs
~~~~~~~~~~~~~~~

If you use the Symfony Flex recipe, import the ``path()`` and ``url()``
functions from the ``assets/router.js`` file:

.. code-block:: javascript

    // assets/my_file.js
    import { path, url } from './router.js';

    path('app_blog_show', { slug: 'hello', _query: { page: 2 }, _fragment: 'comments' });
    // '/blog/hello?page=2#comments'

    path('app_blog_show', { slug: 'hello' }, true);
    // relative path, e.g. 'blog/hello'

    url('app_blog_show', { slug: 'hello' });
    // 'https://example.com/blog/hello'

    url('app_blog_show', { slug: 'hello' }, true);
    // '//example.com/blog/hello'

Query string parameters go in ``_query``. Optional parameters equal to their
default value are omitted, as in PHP.

Localized routes
~~~~~~~~~~~~~~~~

Call localized routes by their name: the current locale picks the right
variant:

.. code-block:: javascript

    path('app_about');                    // '/about' when <html lang="en">
    path('app_about', { _locale: 'fr' }); // '/a-propos'

The current locale is the first one found in:

#. the ``_locale`` parameter given to ``path()`` or ``url()``;
#. the ``_locale`` parameter of the ``context`` given to ``createRouter()``;
#. the ``lang`` attribute of ``<html>`` (``fr-FR`` becomes ``fr_FR``);
#. the ``defaultLocale`` option of ``createRouter()``;
#. ``en``.

The dumped ``var/routes/index.js`` file exports the
``framework.default_locale`` of your application as ``defaultLocale``.

Configuring the request context
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The host, scheme and port come from the current page. Pass a ``context`` to
override them, for example when your application is served from a
sub-directory:

.. code-block:: javascript

    // assets/router.js
    export const { path, url } = createRouter({
        routes,
        context: { baseUrl: '/my-app' },
    });

Handling errors
~~~~~~~~~~~~~~~

``RouteNotFoundError``, ``MissingMandatoryParametersError`` and
``InvalidParameterError`` are thrown in the same situations as in PHP, with
almost the same messages. They are exported by ``@symfony/ux-router``.

There are two differences from PHP. ``InvalidParameterError`` shows the
requirement as JavaScript checks it (``[^/]+`` instead of ``[^/]++``). A
requirement that JavaScript cannot express, like ``\A`` or ``[[:alpha:]]``, is
not checked.

Configuration
-------------

.. code-block:: yaml

    # config/packages/ux_router.yaml
    ux_router:
        # The directory where routes and TypeScript types are dumped.
        # If you change it, also change the import in assets/router.js
        dump_directory: '%kernel.project_dir%/var/routes'

        # Whether TypeScript types are dumped next to the routes
        dump_typescript: true

        # Route name patterns to expose, prefix with "!" to exclude
        routes: []

Q&A
---

What about bundle size?
~~~~~~~~~~~~~~~~~~~~~~~

All the routes you expose are included in the generated
``var/routes/index.js`` file, which means they are included in your final
JavaScript bundle. Use the ``routes`` patterns and the ``expose`` option to
expose only the routes your JavaScript calls.

Backward Compatibility promise
------------------------------

This bundle aims to follow the same Backward Compatibility promise as the
Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

.. _`Symfony routes`: https://symfony.com/doc/current/routing.html
.. _`the Symfony UX initiative`: https://ux.symfony.com/
.. _`AssetMapper`: https://symfony.com/doc/current/frontend/asset_mapper.html
.. _`Symfony Reprise`: https://github.com/symfony/reprise
.. _`Vite`: https://vite.dev/
.. _`Rsbuild`: https://rsbuild.dev/
.. _`Webpack Encore`: https://symfony.com/doc/current/frontend/encore/index.html
.. _`@symfony/ux-router npm package`: https://www.npmjs.com/package/@symfony/ux-router
