# CHANGELOG

## 2.0

-   Support for `stimulus` version 2 was removed and support for `@hotwired/stimulus`
    version 3 was added. See the [@symfony/stimulus-bridge CHANGELOG](https://github.com/symfony/stimulus-bridge/blob/main/CHANGELOG.md#300)
    for more details.
-   All options were changed from `data-` attributes to Stimulus values. See
    The README for updated instructions.
-   Added a new `swup:pre-connect` event.
-   Support added for Symfony 6.

## 1.3

-   [DEPENDENCY CHANGE] `swup` (and a few themes/plugins) is no longer included
    automatically (#93), but `symfony/flex` will automatically add all the
    required libraries your `package.json` file when upgrading. Additionally
    `symfony/flex` 1.13 or higher is now required if installed.
