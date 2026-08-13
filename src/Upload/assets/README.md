# @symfony/ux-upload

JavaScript assets of the [symfony/ux-upload](https://packagist.org/packages/symfony/ux-upload) PHP package.

## Installation

This npm package is reserved for advanced users who need to install JavaScript
dependencies separately from PHP dependencies.

Install the PHP package first:

```shell
composer require symfony/ux-upload
```

When installing this package directly, keep its version identical to the PHP
package version:

```shell
composer require symfony/ux-upload:3.2.0
npm add @symfony/ux-upload@3.2.0
```

## Optional styles

The package does not import CSS automatically. Choose a layout in the Symfony
form and import the corresponding standalone stylesheet when needed:

```php
$builder->add('document', FileUploadType::class, [
    'layout' => 'compact', // or "dropzone"
    'show_preview' => true,
]);
```

```js
import '@symfony/ux-upload/compact.css';
// or: import '@symfony/ux-upload/dropzone.css';
```

The HTML remains usable without either stylesheet. The package form theme owns
the markup and exposes composable Twig blocks; the Stimulus controller only
manages upload behavior and state.

## Resources

- [Documentation](https://symfony.com/bundles/ux-upload/current/index.html)
- [Issue tracker and pull requests](https://github.com/symfony/ux)
