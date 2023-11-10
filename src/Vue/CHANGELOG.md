# CHANGELOG

## 2.13.2

-   Revert "Change JavaScript package to `type: module`"

## 2.13.0

-   Add Symfony 7 support.
-   Change JavaScript package to `type: module`

## 2.9.0

-   Replace `symfony/webpack-encore-bundle` by `symfony/stimulus-bundle` in dependencies

-   Add support for symfony/asset-mapper

-   Minimum PHP version is now 8.1

## 2.7.0

-   Add `assets/src` to `.gitattributes` to exclude source TypeScript files from
    installing.

-   TypeScript types are now included.

## 2.6.0

-   [BC BREAK] The `assets/` directory was moved from `Resources/assets/` to `assets/`. Make
    sure the path in your `package.json` file is updated accordingly.

-   The directory structure of the bundle was updated to match modern best-practices.

## 2.5

-   Added support for lazily-loaded Vue components - #482.

-   Added `vue:before-mount` JavaScript event - #444.

## 2.4

-   Component added
