# CHANGELOG

## 2.9.0

-   Add support for symfony/asset-mapper

-   Minimum required Symfony version is now 5.4

## 2.7.0

-   The JavaScript events now bubble up.

-   Add `assets/src` to `.gitattributes` to exclude source TypeScript files from
    installing.

-   TypeScript types are now included.

## 2.6.0

-   [BC BREAK] The `assets/` directory was moved from `Resources/assets/` to `assets/`. Make
    sure the path in your `package.json` file is updated accordingly.

-   The directory structure of the bundle was updated to match modern best-practices.

## 2.0

-   Support for `stimulus` version 2 was removed and support for `@hotwired/stimulus`
    version 3 was added. See the [@symfony/stimulus-bridge CHANGELOG](https://github.com/symfony/stimulus-bridge/blob/main/CHANGELOG.md#300)
    for more details.
-   The `data-hd-src` attribute was changed to use a Stimulus value called `src`. See the
    updated README for usage.
-   For both JavaScript events - `lazy-image:connect` and `lazy-image:ready` -
    the `event.detail.hd` `Image` instance was moved to `event.detail.image`.
-   Support added for Symfony 6
