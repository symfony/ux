# Symfony UX Dropzone

Symfony UX Dropzone is a Symfony bundle providing light dropzones for file inputs
in Symfony Forms. It is part of [the Symfony UX initiative](https://symfony.com/ux).

It allows visitors to drag and drop files into a container instead of having
to browse their computer for a file.

## Installation

Symfony UX Dropzone requires PHP 7.2+ and Symfony 4.4+.

Install this bundle using Composer and Symfony Flex:

```sh
composer require symfony/ux-dropzone

# Don't forget to install the JavaScript dependencies as well and compile
yarn install --force
yarn encore dev
```

## Usage

The most common usage of Symfony UX Dropzone is to use it as a drop-in replacement of 
the native FileType class:

```php
// ...
use Symfony\UX\Dropzone\Form\DropzoneType;

class CommentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // ...
            ->add('photo', DropzoneType::class)
            // ...
        ;
    }

    // ...
}
```

### Customizing the design

Symfony UX Dropzone provides a default stylesheet in order to ease usage. You can
disable it to add your own design if you wish.

In `assets/controllers.json`, disable the default stylesheet by switching 
the `@symfony/ux-dropzone/src/style.css` autoimport to `false`:

```json
{
    "controllers": {
        "@symfony/ux-dropzone": {
            "dropzone": {
                "enabled": true,
                "webpackMode": "eager",
                "autoimport": {
                    "@symfony/ux-dropzone/src/style.css": false
                }
            }
        }
    },
    "entrypoints": []
}
```

> *Note*: you should put the value to `false` and not remove the line so that Symfony Flex
> won't try to add the line again in the future.

Once done, the default stylesheet won't be used anymore and you can implement your own CSS on
top of the Dropzone.

### Extend the default behavior

Symfony UX Dropzone allows you to extend its default behavior using a custom Stimulus controller:

```js
// mydropzone_controller.js

import { Controller } from 'stimulus';

export default class extends Controller {
    connect() {
        this.element.addEventListener('dropzone:connect', this._onConnect);
        this.element.addEventListener('dropzone:change', this._onChange);
        this.element.addEventListener('dropzone:clear', this._onClear);
    }

    disconnect() {
        // You should always remove listeners when the controller is disconnected to avoid side-effects
        this.element.removeEventListener('dropzone:connect', this._onConnect);
        this.element.removeEventListener('dropzone:change', this._onChange);
        this.element.removeEventListener('dropzone:clear', this._onClear);
    }

    _onConnect(event) {
        // The dropzone was just created
    }

    _onChange(event) {
        // The dropzone just changed
    }

    _onClear(event) {
        // The dropzone has just been cleared
    }
}
```

Then in your form, add your controller as an HTML attribute:

```php
// ...
use Symfony\UX\Dropzone\Form\DropzoneType;

class CommentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // ...
            ->add('photo', DropzoneType::class, [
                'attr' => ['data-controller' => 'mydropzone'],
            ])
            // ...
        ;
    }

    // ...
}
```

## Backward Compatibility promise

This bundle aims at following the same Backward Compatibility promise as the Symfony framework:
[https://symfony.com/doc/current/contributing/code/bc.html](https://symfony.com/doc/current/contributing/code/bc.html)

However it is currently considered
[**experimental**](https://symfony.com/doc/current/contributing/code/experimental.html),
meaning it is not bound to Symfony's BC policy for the moment.

## Run tests

### PHP tests

```sh
php vendor/bin/phpunit
```

### JavaScript tests

```sh
cd Resources/assets
yarn test
```
