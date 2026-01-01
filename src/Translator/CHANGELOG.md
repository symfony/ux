# CHANGELOG

## 3.0.0

-   Minimum required Symfony version is now 6.4
-   Minimum required PHP version is now 8.2

## 2.32

-   **[BC BREAK]** Refactor API to use string-based translation keys instead of generated constants.

    Translation keys are now simple strings instead of TypeScript constants.
    The main advantages are:
    - You can now use **exactly the same translation keys** as in your Symfony PHP code
    - Simpler and more readable code
    - No need to memorize generated constant names
    - No need to import translation constants: smaller files
    - And you can still get autocompletion and type-safety :rocket:

    **Before:**
    ```typescript
    import { trans } from '@symfony/ux-translator';
    import { SYMFONY_GREAT } from '@app/translations';

    trans(SYMFONY_GREAT);
    ```

    **After:**
    ```typescript
    import { createTranslator } from '@symfony/ux-translator';
    import { messages } from '../var/translations/index.js';

    const { trans } = createTranslator({ messages });
    trans('symfony.great');
    ```

    The global functions (`setLocale`, `getLocale`, `setLocaleFallbacks`, `getLocaleFallbacks`, `throwWhenNotFound`)
    have been replaced by a new `createTranslator()` factory function that returns an object with these methods.

    **Tree-shaking:** While tree-shaking of individual translation keys is no longer possible, modern build tools,
    caching strategies, and compression techniques (Brotli, gzip) make this negligible in 2025.
    You can use the `keys_patterns` configuration option to filter dumped translations by pattern
    for further reducing bundle size.

    **For AssetMapper users:** You can remove the following entries from your `importmap.php`:
    ```php
    '@app/translations' => [
        'path' => './var/translations/index.js',
    ],
    '@app/translations/configuration' => [
        'path' => './var/translations/configuration.js',
    ],
    ```

    **Note:** This is a breaking change, but the UX Translator component is still experimental.

-   **[BC BREAK]** Refactor `TranslationsDumper` to accept configuration options via `dump()` method parameters, instead of constructor arguments or method calls:
        - Removed `$dumpDir` and `$dumpTypeScript` constructor arguments
        - Removed `TranslationsDumper::addIncludedDomain()` and `TranslationsDumper::addExcludedDomain()` methods

    **Note:** This is a breaking change, but the UX Translator component is still experimental.

-   Add configuration `ux_translator.dump_typescript`  to enable/disable TypeScript types dumping,
    default to `true`. Generating TypeScript types is useful when developing,
    but not in production when using the AssetMapper (which does not use these types).

-   Add `keys_patterns` configuration option to filter dumped translations by key patterns (e.g., `app.*`, `!*.internal`)

## 2.30

-  Ensure compatibility with PHP 8.5

## 2.29.0

-  Add Symfony 8 support

## 2.22.0

-   Support both the Symfony format (`fr_FR`) and W3C specification (`fr-FR`) for locale subcodes.

## 2.20.0

-   Add `throwWhenNotFound` function to configure the behavior when a translation is not found.

## 2.19.0

-   Add configuration to filter dumped translations by domain.

## 2.16.0

-   Increase version range of `intl-messageformat` to `^10.5.11`, in order to see
    a faster implementation of ICU messages parsing. #1443

## 2.13.2

-   Revert "Change JavaScript package to `type: module`"

## 2.13.0

-   Add Symfony 7 support.
-   Change JavaScript package to `type: module`

## 2.9.0

-   Add support for symfony/asset-mapper

## 2.8.0

-   Component added
