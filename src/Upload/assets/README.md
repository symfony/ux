# @symfony/ux-upload

JavaScript assets of the [symfony/ux-upload](https://packagist.org/packages/symfony/ux-upload) PHP package.

## Installation

This npm package is **reserved for advanced users** who want to decouple their JavaScript dependencies from their PHP dependencies (e.g., when building Docker images, running JavaScript-only pipelines, etc.).

We **strongly recommend not installing this package directly**, but instead install the PHP package [symfony/ux-upload](https://packagist.org/packages/symfony/ux-upload) in your Symfony application with [Flex](https://github.com/symfony/flex) enabled.

If you still want to install this package directly, please make sure its version exactly matches [symfony/ux-upload](https://packagist.org/packages/symfony/ux-upload) PHP package version:

```shell
composer require symfony/ux-upload:X.Y.Z
npm add @symfony/ux-upload@X.Y.Z
```

**Tip:** Your `package.json` file will be automatically modified by [Flex](https://github.com/symfony/flex) when installing or upgrading a PHP package. To prevent this behavior, ensure to **use at least Flex 1.22.0 or 2.5.0**, and run `composer config --json "extra.symfony/flex.synchronize_package_json" false`.

## Resources

- [Documentation](https://symfony.com/bundles/ux-upload/current/index.html)
- [Report issues](https://github.com/symfony/ux/issues) and
  [send Pull Requests](https://github.com/symfony/ux/pulls)
  in the [main Symfony UX repository](https://github.com/symfony/ux)
