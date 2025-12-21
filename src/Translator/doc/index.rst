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
    :ref:`Using with AssetMapper`_.

.. caution::

    Before you start, make sure you have `StimulusBundle configured in your app`_.

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-translator

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

    import { createTranslator } from '@symfony/ux-translator';
    import { messages, localeFallbacks } from '../var/translations/index.js';

    const translator = createTranslator({
        messages,
        localeFallbacks,
    });

    // Allow you to use `import { trans } from './translator';` in your assets
    export const { trans } = translator;

Using with WebpackEncore
~~~~~~~~~~~~~~~~~~~~~~~~

If you're using WebpackEncore, install your assets and restart Encore (not
needed if you're using AssetMapper):

.. code-block:: terminal

    $ npm install --force
    $ npm run watch

.. note::

    For more complex installation scenarios, you can install the JavaScript assets through the `@symfony/ux-translator npm package`_

Using with AssetMapper
~~~~~~~~~~~~~~~~~~~~~~

Using this library with AssetMapper is possible.

When installing with AssetMapper, Flex will add a new item to your ``importmap.php``
file::

    '@symfony/ux-translator' => [
        'path' => './vendor/symfony/ux-translator/assets/dist/translator_controller.js',
    ],

Usage
-----

When warming up the Symfony cache, your translations will be dumped as JavaScript into the ``var/translations/`` directory.
For a better developer experience, TypeScript types definitions are also generated aside those JavaScript files.

Then, you will be able to import the ``trans()`` function in your assets and use translation keys as simple strings,
exactly as you would in your Symfony PHP code.

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

You can also filter dumped translations by translation key patterns using wildcards:

.. code-block:: yaml

    ux_translator:
        keys_patterns: ['app.*', 'user.*']  # Include only keys starting with 'app.' or 'user.'
        keys_patterns: ['!*.internal', '!debug.*']  # Exclude keys ending with '.internal' or starting with 'debug.'
        keys_patterns: ['app.*', '!app.internal.*']  # Include 'app.*' but exclude 'app.internal.*'

The wildcard ``*`` matches any characters. You can prefix a pattern with ``!`` to exclude keys matching that pattern.

Disabling TypeScript types dump
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

By default, TypeScript types definitions are generated alongside the dumped JavaScript translations.
This provides autocompletion and type-safety when using the ``trans()`` function in your assets.

Even if they are useful when developing, dumping these TypeScript types is useless in production if you use the
AssetMapper, because these files will never be used.

You can disable the TypeScript types dump by adding the following configuration:

.. code-block:: yaml

    when@prod:
        ux_translator:
            dump_typescript: false

Configuring the default locale
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

By default, the default locale is ``en`` (English) that you can configure through many ways (in order of priority):

#. By passing the locale directly to the ``createTranslator()`` function
#. With ``setLocale('de')`` or ``setLocale('de_AT')`` from your ``assets/translator.js`` file
#. Or with ``<html data-symfony-ux-translator-locale="{{ app.request.locale }}">`` attribute (e.g., ``de_AT`` or ``de`` using Symfony locale format)
#. Or with ``<html lang="{{ app.request.locale|replace({ '_': '-' }) }}">`` attribute (e.g., ``de-AT`` or ``de`` following the `W3C specification on language codes`_)

Detecting missing translations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

By default, the translator will return the translation key if the translation is missing.

You can change this behavior by calling ``setThrowWhenNotFound(true)``:

.. code-block:: diff

      // assets/translator.js

      import { createTranslator } from '@symfony/ux-translator';
      import { messages, localeFallbacks } from '../var/translations/index.js';

      const translator = createTranslator({
          messages,
          localeFallbacks,
    +     throwWhenNotFound: true, // either when creating the translator
      });

    + // Or later in your code
      export const { trans, setThrowWhenNotFound } = translator;

Importing and using translations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you use the Symfony Flex recipe, you can import the ``trans()`` function from the file ``assets/translator.js``.

You can then use translation keys as simple strings, exactly as you would in your Symfony PHP code:

.. code-block:: javascript

    // assets/my_file.js

    import { trans } from './translator';

    // No parameters, uses the default domain ("messages") and the default locale
    trans('translation.simple');

    // Two parameters "count" and "foo", uses the default domain ("messages") and the default locale
    trans('translation.with.parameters', { count: 123, foo: 'bar' });

    // No parameters, uses the default domain ("messages") and the default locale
    trans('translation.multi.domains');
    // Same as above, but uses the "domain2" domain
    trans('translation.multi.domains', {}, 'domain2');
    // Same as above, but uses the "domain3" domain
    trans('translation.multi.domains', {}, 'domain3');

    // No parameters, uses the default domain ("messages") and the default locale
    trans('translation.multi.locales');
    // Same as above, but uses the "fr" locale
    trans('translation.multi.locales', {}, 'messages', 'fr');
    // Same as above, but uses the "it" locale
    trans('translation.multi.locales', {}, 'messages', 'it');

You will get autocompletion and type-safety for translation keys, parameters, domains, and locales.

Q&A
---

What about bundle size?
~~~~~~~~~~~~~~~~~~~~~~~

All your translations (extracted from the configured domains) are included in the generated ``var/translations/index.js`` file,
which means they will be included in your final JavaScript bundle).

However, modern build tools, caching strategies, and compression techniques (Brotli, gzip)
make this negligible in 2025. You can use the ``keys_patterns`` configuration option
to filter dumped translations by pattern if you need to further reduce bundle size.

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

.. _`Symfony Translator`: https://symfony.com/doc/current/translation.html
.. _`the Symfony UX initiative`: https://ux.symfony.com/
.. _StimulusBundle configured in your app: https://symfony.com/bundles/StimulusBundle/current/index.html
.. _`ICU Message Format`: https://symfony.com/doc/current/reference/formats/message_format.html
.. _`W3C specification on language codes`: https://www.w3.org/TR/html401/struct/dirlang.html#h-8.1.1
.. _`@symfony/ux-translator npm package`: https://www.npmjs.com/package/@symfony/ux-translator
