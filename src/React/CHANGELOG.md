# CHANGELOG

## 2.30

-  Ensure compatibility with PHP 8.5

## 2.29.0

-  Add Symfony 8 support

## 2.28.0

-   [BC BREAK] By modernizing our building tools, the file `dist/render_controller.js` now does not contain any useless
    code related to `development` environment.

    This file is now smaller and faster to load, but the imported module changed from `react-dom` to `react-dom/client`:
    - You **are not impacted** if you are using the Symfony AssetMapper and Symfony Flex, or Webpack Encore.
    - You **are impacted** if you are using the Symfony AssetMapper but **not** Symfony Flex, you need to :
    ```shell
    php bin/console importmap:remove react-dom
    php bin/console importmap:require react-dom/client
    ```

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
