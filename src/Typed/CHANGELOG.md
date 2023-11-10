# CHANGELOG

## 2.13.2

-   Revert "Change JavaScript package to `type: module`"

## 2.13.0

-   Change JavaScript package to `type: module`

## 2.9.0

-   A TypedBundle was added - which allows for integration with symfony/asset-mapper.

-   Add support for symfony/asset-mapper

## 2.7.0

-   Add `assets/src` to `.gitattributes` to exclude source TypeScript files from
    installing.

-   TypeScript types are now included.

## 2.6.0

-   [BC BREAK] The `assets/` directory was moved from `Resources/assets/` to `assets/`. Make
    sure the path in your `package.json` file is updated accordingly.
