# Symfony UX Cropper.js

Symfony UX Cropper.js is a Symfony bundle integrating the [Cropper.js](https://fengyuanchen.github.io/cropperjs/)
library in Symfony applications. It is part of [the Symfony UX initiative](https://symfony.com/ux).

## Installation

Symfony UX Cropper.js requires PHP 7.2+ and Symfony 4.4+.

Install this bundle using Composer and Symfony Flex:

```sh
composer require symfony/ux-cropperjs

# Don't forget to install the JavaScript dependencies as well and compile
yarn install --force
yarn encore dev
```

Also make sure you have at least version 2.0 of [@symfony/stimulus-bridge](https://github.com/symfony/stimulus-bridge)
in your `package.json` file.

## Usage

To use Symfony UX Cropper.js, inject the `CropperInterface` service,
create a Crop object, and use this object inside a standard form:

```php
// ...
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\Cropperjs\Form\CropperType;
use Symfony\UX\Cropperjs\Factory\CropperInterface;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index(CropperInterface $cropper, Request $request): Response
    {
        $crop = $cropper->createCrop('/server/path/to/the/image.jpg');
        $crop->setCroppedMaxSize(2000, 1500);

        $form = $this->createFormBuilder(['crop' => $crop])
            ->add('crop', CropperType::class, [
                'public_url' => '/public/url/to/the/image.jpg',
                'cropper_options' => [
                    'aspectRatio' => 2000 / 1500,
                ],
            ])
            ->getForm()
        ;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get the cropped image data (as a string)
            $crop->getCroppedImage();

            // Create a thumbnail of the cropped image (as a string)
            $crop->getCroppedThumbnail(200, 150);

            // ...
        }

        return $this->render('home/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
```

These `cropper_options` can be any [the Cropper.js option](https://github.com/fengyuanchen/cropperjs/blob/main/README.md#options).

Once created in PHP, a crop form is a normal form, meaning you can display it using Twig
as you would normally with any form:

```twig
{{ form(form) }}
```

### Extend the default behavior

Symfony UX Cropper.js allows you to extend its default behavior using a custom Stimulus controller:

```js
// mycropper_controller.js

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.element.addEventListener('cropperjs:pre-connect', this._onPreConnect);
        this.element.addEventListener('cropperjs:connect', this._onConnect);
    }

    disconnect() {
        // You should always remove listeners when the controller is disconnected to avoid side effects
        this.element.removeEventListener('cropperjs:pre-connect', this._onConnect);
        this.element.removeEventListener('cropperjs:connect', this._onConnect);
    }

    _onPreConnect(event) {
        // The cropper has not yet been created and options can be modified
        console.log(event.detail.options);
        console.log(event.detail.img);
    }

    _onConnect(event) {
        // The cropper was just created and you can access details from the event
        console.log(event.detail.cropper);
        console.log(event.detail.options);
        console.log(event.detail.img);

        // For instance you can listen to additional events
        event.detail.img.addEventListener('cropend', function () {
            // ...
        });
    }
}
```

Then in your form, add your controller as an HTML attribute:

```php
$form = $this->createFormBuilder(['crop' => $crop])
    ->add('crop', CropperType::class, [
        'public_url' => '/public/url/to/the/image.jpg',
        'cropper_options' => [
            'aspectRatio' => 2000 / 1800,
        ],
        'attr' => ['data-controller' => 'mycropper'],
    ])
    ->getForm()
;
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
