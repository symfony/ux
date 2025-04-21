# @symfony/ux-google-map

JavaScript assets of the [symfony/ux-google-map](https://packagist.org/packages/symfony/ux-google-map) PHP package.

## Installation

This npm package is **reserved for advanced users** who want to decouple their JavaScript dependencies from their PHP dependencies (e.g., when building Docker images, running JavaScript-only pipelines, etc.).

We **strongly recommend not installing this package directly**, but instead  install the PHP package [symfony/ux-google-map](https://packagist.org/packages/symfony/ux-google-map) in your Symfony application with [Flex](https://github.com/symfony/flex) enabled.

If you still want to install this package directly, please make sure its version exactly matches [symfony/ux-google-map](https://packagist.org/packages/symfony/ux-google-map) PHP package version:
```shell
composer require symfony/ux-google-map:2.23.0
npm add @symfony/ux-google-map@2.23.0
```

**Tip:** Your `package.json` file will be automatically modified by [Flex](https://github.com/symfony/flex) when installing or upgrading a PHP package. To prevent this behavior, ensure to **use at least Flex 1.22.0 or 2.5.0**, and run `composer config extra.symfony.flex.synchronize_package_json false`.

## Resources

-   [Documentation](https://github.com/symfony/ux/tree/2.x/src/Map/src/Bridge/Google)
-   [Report issues](https://github.com/symfony/ux/issues) and
    [send Pull Requests](https://github.com/symfony/ux/pulls)
    in the [main Symfony UX repository](https://github.com/symfony/ux)
