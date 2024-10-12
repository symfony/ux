Symfony UX Translator
=====================

**EXPERIMENTAL** This component is currently experimental and is likely
to change, or even change drastically.

Symfony UX Translator is a Symfony bundle providing the same mechanism as `Symfony Translator`_
in JavaScript with a TypeScript integration, in Symfony applications. It is part of `the Symfony UX initiative`_.

The `ICU Message Format`_ is also supported.

Installation
------------

.. note::

    This package works best with WebpackEncore. To use it with AssetMapper, see
    :ref:`Using with AssetMapper <using-with-asset-mapper>`.

.. caution::

    Before you start, make sure you have `StimulusBundle configured in your app`_.

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-translator

If you're using WebpackEncore, install your assets and restart Encore (not
needed if you're using AssetMapper):

.. code-block:: terminal

    $ npm install --force
    $ npm run watch

After installing the bundle, the following file should be created, thanks to the Symfony Flex recipe:

.. code-block:: javascript

    // assets/translator.js

    /*
     * This file is part of the Symfony UX Translator package.
     *
     * If folder "../var/translations" does not exist, or some translations are missing,
     * you must warmup your Symfony cache to refresh JavaScript translations.
     *
     * If you use TypeScript, you can rename this file to "translator.ts" to take advantage of types checking.
     */

    import { trans, getLocale, setLocale, setLocaleFallbacks } from '@symfony/ux-translator';
    import { localeFallbacks } from '../var/translations/configuration';

    setLocaleFallbacks(localeFallbacks);

    export { trans }
    export * from '../var/translations';

Usage
-----

When warming up the Symfony cache, your translations will be dumped as JavaScript into the ``var/translations/`` directory.
For a better developer experience, TypeScript types definitions are also generated aside those JavaScript files.

Then, you will be able to import those JavaScript translations in your assets.
Don't worry about your final bundle size, only the translations you use will be included in your final bundle, thanks to the `tree shaking <https://webpack.js.org/guides/tree-shaking/>`_.

Configuring the dumped translations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

By default, all your translations will be exported. You can restrict the dumped messages by either
including or excluding translation domains in your ``config/packages/ux_translator.yaml`` file:

.. code-block:: yaml

    ux_translator:
            domains: ~    # Include all the domains

            domains: foo  # Include only domain 'foo'
            domains: '!foo' # Include all domains, except 'foo'

            domains: [foo, bar]   # Include only domains 'foo' and 'bar'
            domains: ['!foo', '!bar'] # Include all domains, except 'foo' and 'bar'


Configuring the default locale
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

By default, the default locale is ``en`` (English) that you can configure through many ways (in order of priority):

#. With ``setLocale('your-locale')`` from ``@symfony/ux-translator`` package
#. Or with ``<html data-symfony-ux-translator-locale="your-locale">`` attribute
#. Or with ``<html lang="your-locale">`` attribute

Detecting missing translations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

By default, the translator will return the translation key if the translation is missing.

You can change this behavior by calling ``throwWhenNotFound(true)``:

.. code-block:: diff

      // assets/translator.js

    - import { trans, getLocale, setLocale, setLocaleFallbacks } from '@symfony/ux-translator';
    + import { trans, getLocale, setLocale, setLocaleFallbacks, throwWhenNotFound } from '@symfony/ux-translator';
      import { localeFallbacks } from '../var/translations/configuration';

      setLocaleFallbacks(localeFallbacks);
    + throwWhenNotFound(true)

      export { trans }
      export * from '../var/translations';

Importing and using translations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you use the Symfony Flex recipe, you can import the ``trans()`` function and your translations in your assets from the file ``assets/translator.js``.

Translations are available as named exports, by using the translation's id transformed in uppercase snake-case (e.g.: ``my.translation`` becomes ``MY_TRANSLATION``),
so you can import them like this:

.. code-block:: javascript

    // assets/my_file.js

    import {
        trans,
        TRANSLATION_SIMPLE,
        TRANSLATION_WITH_PARAMETERS,
        TRANSLATION_MULTI_DOMAINS,
        TRANSLATION_MULTI_LOCALES,
    } from './translator';

    // No parameters, uses the default domain ("messages") and the default locale
    trans(TRANSLATION_SIMPLE);

    // Two parameters "count" and "foo", uses the default domain ("messages") and the default locale
    trans(TRANSLATION_WITH_PARAMETERS, { count: 123, foo: 'bar' });

    // No parameters, uses the default domain ("messages") and the default locale
    trans(TRANSLATION_MULTI_DOMAINS);
    // Same as above, but uses the "domain2" domain
    trans(TRANSLATION_MULTI_DOMAINS, {}, 'domain2');
    // Same as above, but uses the "domain3" domain
    trans(TRANSLATION_MULTI_DOMAINS, {}, 'domain3');

    // No parameters, uses the default domain ("messages") and the default locale
    trans(TRANSLATION_MULTI_LOCALES);
    // Same as above, but uses the "fr" locale
    trans(TRANSLATION_MULTI_LOCALES, {}, 'messages', 'fr');
    // Same as above, but uses the "it" locale
    trans(TRANSLATION_MULTI_LOCALES, {}, 'messages', 'it');

.. _using-with-asset-mapper:

Using with AssetMapper
----------------------

Using this library with AssetMapper is possible, but is currently experimental
and may not be ready yet for production.

When installing with AssetMapper, Flex will add a few new items to your ``importmap.php``
file. 2 of the new items are::

    '@app/translations' => [
        'path' => 'var/translations/index.js',
    ],
    '@app/translations/configuration' => [
        'path' => 'var/translations/configuration.js',
    ],

These are then imported in your ``assets/translator.js`` file. This setup is
very similar to working with WebpackEncore. However, the ``var/translations/index.js``
file contains *every* translation in your app, which is not ideal for production
and may even leak translations only meant for admin areas. Encore solves this via
tree-shaking, but the AssetMapper component does not. There is not, yet, a way to
solve this properly with the AssetMapper component.

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

.. _`Symfony Translator`: https://symfony.com/doc/current/translation.html
.. _`the Symfony UX initiative`: https://ux.symfony.com/
.. _StimulusBundle configured in your app: https://symfony.com/bundles/StimulusBundle/current/index.html
.. _`ICU Message Format`: https://symfony.com/doc/current/reference/formats/message_format.html
