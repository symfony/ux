Production
==========

Deployment order
----------------

``cache:warmup`` writes ``var/ux_css/styles.css``, in a compact form. The
asset build reads it, so it runs after:

.. code-block:: terminal

    $ APP_ENV=prod php bin/console cache:warmup

    # then, depending on your setup
    $ php bin/console asset-map:compile
    $ npm run build

``composer install`` usually runs ``cache:clear`` through Symfony Flex, which
warms up the cache too. The cache warmer of this bundle is not optional, so
the file is also written when the cache is built on the first request.

With a multi-stage Docker build, the Node.js stage needs the file produced by
the PHP stage. As with the translations of UX Translator, copy
``var/ux_css/styles.css`` from the PHP stage before running the build.

Failing the build on mistakes
-----------------------------

Outside of debug mode, ``cache:warmup`` fails on the first invalid ``css()``
call, with the template and the line. A deployment that warms up the cache
therefore never ships a template with a mistake. See :doc:`validation`.

Performance
-----------

What each part costs, measured on a laptop with PHP 8.4, opcache on and
Xdebug off:

======================================================== =====================
Operation                                                Time
======================================================== =====================
``css()`` with a hash written in the template            nothing at runtime
``css()`` with a runtime value, per call                 1 to 10 µs
Dev request, no template changed, 200 templates          about 2 ms
Dev request, one template changed, 200 templates         about 15 ms
``cache:warmup`` of the CSS, 200 templates               about 180 ms
======================================================== =====================

The template figures come from 200 templates with five ``css()`` calls each.

A hash written in the template becomes a plain string in the compiled
template: rendering it costs nothing more than printing it. In dev, the bundle
lists the templates and checks the modification time of each one on every
request. It only rereads the templates that changed.

On the 197 templates of ux.symfony.com, which do not use ``css()`` yet, a dev
request with no change costs about 2 ms: the cost of listing and checking the
files.
