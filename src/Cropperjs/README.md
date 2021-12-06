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

Also make sure you have at least version 3.0 of [@symfony/stimulus-bridge](https://github.com/symfony/stimulus-bridge)
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
     * #[Route('/', name: 'app_homepage')]
     */
    public function index(CropperInterface $cropper, Request $request): Response
    {
        $crop = $cropper->createCrop('/server/path/to/the/image.jpg');
        $crop->setCroppedMaxSize(2000, 1500);

        $form = $this->createFormBuilder(['crop' => $crop])
            ->add('crop', CropperType::class, [
                'public_url' => '/public/url/to/the/image.jpg',
                'aspect_ratio' => 2000 / 1500,
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

You can pass the following options to the `CropperType` field:

```php
$form = $this->createFormBuilder(['crop' => $crop])
    ->add('crop', CropperType::class, [
        'public_url' => '/public/url/to/the/image.jpg',
        'view_mode' => 1,
        'drag_mode' => 'move',
        'initial_aspect_ratio' => 2000 / 1800,
        'aspect_ratio' => 2000 / 1800,
        'responsive' => true,
        'restore' => true,
        'check_cross_origin' => true,
        'check_orientation' => true,
        'modal' => true,
        'guides' => true,
        'center' => true,
        'highlight' => true,
        'background' => true,
        'auto_crop' => true,
        'auto_crop_area' => 0.1,
        'movable' => true,
        'rotatable' => true,
        'scalable' => true,
        'zoomable' => true,
        'zoom_on_touch' => true,
        'zoom_on_wheel' => true,
        'wheel_zoom_ratio' => 0.2,
        'crop_box_movable' => true,
        'crop_box_resizable' => true,
        'toggle_drag_mode_on_dblclick' => true,
        'min_container_width' => 200,
        'min_container_height' => 100,
        'min_canvas_width' => 0,
        'min_canvas_height' => 0,
        'min_crop_box_width' => 0,
        'min_crop_box_height' => 0,
    ])
    ->getForm()
;
```

These options are associated to [the Cropper.js options](https://github.com/fengyuanchen/cropperjs/blob/master/README.md#options).

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
        this.element.addEventListener('cropperjs:connect', this._onConnect);
    }

    disconnect() {
        // You should always remove listeners when the controller is disconnected to avoid side effects
        this.element.removeEventListener('cropperjs:connect', this._onConnect);
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
        'aspect_ratio' => 2000 / 1800,
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
