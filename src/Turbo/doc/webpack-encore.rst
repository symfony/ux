How to Use Symfony UX Turbo with WebpackEncore
==============================================

Installation
------------

After installing Symfony UX Turbo, install your assets and restart Encore:

.. code-block:: terminal

    $ npm install --force
    $ npm run watch

.. note::

    For more complex installation scenarios, you can install the JavaScript
    assets through the `@symfony/ux-turbo npm package`_.

.. _`@symfony/ux-turbo npm package`: https://www.npmjs.com/package/@symfony/ux-turbo

Reloading When a JavaScript/CSS File Changes
--------------------------------------------

Turbo Drive can automatically perform a full refresh if the content of
one of your CSS or JS files *changes*, to ensure that your users always
have the latest version.

First, verify that you have versioning enabled so that your filenames
change when the file contents change:

.. code-block:: javascript

   // webpack.config.js

   Encore.
       // ...
       .enableVersioning(Encore.isProduction())

Then add a ``data-turbo-track="reload"`` attribute to all of your
``script`` and ``link`` tags:

.. code-block:: yaml

   # config/packages/webpack_encore.yaml
   webpack_encore:
       # ...

       script_attributes:
           defer: true
           'data-turbo-track': reload
       link_attributes:
           'data-turbo-track': reload

For more info, see: `Turbo Reloading When Assets Change`_.

.. _`Turbo Reloading When Assets Change`: https://turbo.hotwired.dev/handbook/drive#reloading-when-assets-change
