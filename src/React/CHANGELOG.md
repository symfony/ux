# CHANGELOG

## 2.28.0

-   [BC BREAK] If you are using the Symfony AssetMapper but **not** Symfony Flex,
    you need to upgrade your `importmap.php` and change the asset `react-dom` to `react-dom/client`,
    and run `php bin/console importmap:install`.

    Symfony Flex or Webpack Encore users are not affected.

## 2.26.0

-   Improve error handling when resolving a React component

## 2.21.0

-   Add `permanent` option to the `react_component` Twig function, to prevent the
    _unmounting_ when the component is deconnected and immediately re-connected

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
