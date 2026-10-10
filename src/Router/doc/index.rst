Symfony UX Router
=================

**EXPERIMENTAL** This component is currently experimental and is likely to
change, or even change drastically.

Symfony UX Router generates the URLs of your Symfony routes in JavaScript,
with the same result as the ``path()`` and ``url()`` Twig functions. With
TypeScript, route names and parameters are type-checked.

It works with `AssetMapper`_ and with JavaScript bundlers (Webpack Encore,
Vite, Rsbuild...).

Installation
------------

.. code-block:: terminal

    $ composer require symfony/ux-router

If you're using WebpackEncoreBundle, install your assets and restart Encore
(not needed if you're using AssetMapper):

.. code-block:: terminal

    $ npm install --force
    $ npm run watch

After installing the bundle, the following file should be created, thanks to
the Symfony Flex recipe:

.. code-block:: javascript

    // assets/router.js
    import { createRouter } from '@symfony/ux-router';
    import { routes } from '../var/routes/index.js';

    export const { path, url } = createRouter({ routes });

If you use TypeScript, rename it to ``router.ts`` to type-check route names
and parameters.

Exposing routes
---------------

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

Exposed routes are dumped in ``var/routes/`` when the cache is warmed up
(``cache:clear``, ``cache:warmup``). In the ``dev`` environment, they are
dumped again on the next request after a route changes. You can also dump
them manually:

.. code-block:: terminal

    $ php bin/console ux:router:warm-cache

Usage
-----

.. code-block:: javascript

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
default value are omitted, like in PHP.

Localized routes
~~~~~~~~~~~~~~~~

Call localized routes by their name: the current locale picks the right
variant:

.. code-block:: javascript

    path('app_about');                    // '/about' when <html lang="en">
    path('app_about', { _locale: 'fr' }); // '/a-propos'

The current locale is read from the ``lang`` attribute of ``<html>``
(``fr-FR`` becomes ``fr_FR``), and falls back to ``en``.

Request context
~~~~~~~~~~~~~~~

The host, scheme and port come from the current page. Pass a ``context`` to
override them, for example when your application is served from a
sub-directory:

.. code-block:: javascript

    export const { path, url } = createRouter({
        routes,
        context: { baseUrl: '/my-app' },
    });

Errors
~~~~~~

``RouteNotFoundError``, ``MissingMandatoryParametersError`` and
``InvalidParameterError`` are thrown in the same situations as in PHP, with
almost the same messages. They are exported by ``@symfony/ux-router``.

There are two differences with PHP. ``InvalidParameterError`` shows the
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

Using with AssetMapper
----------------------

Nothing to configure: the bundle registers the dump directory and the
``@symfony/ux-router`` package in AssetMapper.

AssetMapper ignores the TypeScript types, so you can stop dumping them in
production:

.. code-block:: yaml

    # config/packages/ux_router.yaml
    when@prod:
        ux_router:
            dump_typescript: false

Keep them if you build your assets with a bundler and TypeScript: the build
needs them to type-check your code.

Backward Compatibility promise
------------------------------

This bundle aims to follow the same Backward Compatibility promise as the
Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

.. _`AssetMapper`: https://symfony.com/doc/current/frontend/asset_mapper.html
