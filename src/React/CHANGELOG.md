# CHANGELOG

## 2.13.2

-   Revert "Change JavaScript package to `type: module`"

## 2.13.0

-   Add Symfony 7 support.
-   Change JavaScript package to `type: module`

## 2.9.0

-   Add support for symfony/asset-mapper

-   Replace `symfony/webpack-encore-bundle` by `symfony/stimulus-bundle` in dependencies

-   Minimum PHP version is now 8.1

## 2.7.0

-   Add `assets/src` to `.gitattributes` to exclude source TypeScript files from
    installing.

-   TypeScript types are now included.

## 2.6.0

-   [BC BREAK] The `assets/` directory was moved from `Resources/assets/` to `assets/`. Make
    sure the path in your `package.json` file is updated accordingly.

-   The directory structure of the bundle was updated to match modern best-practices.

## 2.2

-   Component added
