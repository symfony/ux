# CHANGELOG

## 3.2

- **[BC BREAK]** Rename the `ux-native:dump` command to `ux:native:build-configs`
  to follow the `ux:<...>` naming convention and clarify its purpose.

    **Note:** This is a breaking change, but the UX Native component is still experimental.

## 2.35

- Add `assets/` to ease the installation of `@hotwired/stimulus` and `@hotwired/hotwire-native-bridge` JavaScript dependencies.
  Thanks to Symfony Flex, they should be automatically added to your `package.json` (when using Webpack Encore), or `importmap.php` (when using AssetMapper)
- Allow Symfony UX 3.x packages

## 2.33

- Create the component
