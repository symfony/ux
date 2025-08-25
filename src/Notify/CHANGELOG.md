# CHANGELOG

## 3.0.0

-   Minimum required Symfony version is now 6.4
-   Minimum required PHP version is now 8.2

## 2.30

- Ensure compatibility with PHP 8.5

## 2.29.0

-  Add Symfony 8 support

## 2.24.0

-   Added `options` to Notification

## 2.13.2

-   Revert "Change JavaScript package to `type: module`"

## 2.13.0

-   Add Symfony 7 support.
-   Change JavaScript package to `type: module`

## 2.9.0

-   Add support for symfony/asset-mapper

-   Replace `symfony/webpack-encore-bundle` by `symfony/stimulus-bundle` in dependencies

## 2.7.0

-   Add `assets/src` to `.gitattributes` to exclude source TypeScript files from
    installing.

-   TypeScript types are now included.

## 2.6.0

-   [BC BREAK] The `assets/` directory was moved from `Resources/assets/` to `assets/`. Make
    sure the path in your `package.json` file is updated accordingly.

-   The directory structure of the bundle was updated to match modern best-practices.
