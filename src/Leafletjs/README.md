# Symfony UX Leaflet.js

Symfony UX Leaflet.js is a Symfony bundle integrating the [Leaflet](https://leafletjs.com/)
library in Symfony applications. It is part of [the Symfony UX initiative](https://symfony.com/ux).

## Installation

Symfony UX Leaflet requires PHP 7.2+ and Symfony 4.4+.

Install this bundle using Composer and Symfony Flex:

```sh
composer require symfony/ux-leafletjs

# Don't forget to install the JavaScript dependencies as well and compile
yarn install --force
yarn encore dev
```

## Usage

To use Symfony UX Leaflet.js, inject the `LeafletBuilderInterface` service and
create maps in PHP:

```php
// ...
use Symfony\UX\Leafletjs\Builder\LeafletBuilderInterface;
use Symfony\UX\Leafletjs\Model\Map;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index(LeafletBuilderInterface $mapBuilder): Response
    {
        $map = $mapBuilder->createMap(51.505, -0.09, 13);
        $map->setMapOptions(['minZoom' => 1, 'maxZoom' => 8]);

        return $this->render('home/index.html.twig', [
            'map' => $map,
        ]);
    }
}
```

Map options are provided as-is to Leaflet. You can read
[Leaflet documentation](https://leafletjs.com/reference-1.7.1.html#map-factory) to discover them all.

Once created in PHP, a map can be displayed using Twig:

```twig
{{ render_map(map) }}

{# You can pass HTML attributes as a second argument to add them on the <div> tag #}
{{ render_map(map, {'class': 'my-map'}) }}
```

### Extend the default behavior

Symfony UX Leaflet.js allows you to extend its default behavior using a custom Stimulus controller:

```js
// mymap_controller.js

import { Controller } from 'stimulus';

export default class extends Controller {
    connect() {
        this.element.addEventListener('leafletjs:connect', this._onConnect);
    }

    disconnect() {
        // You should always remove listeners when the controller is disconnected to avoid side effects
        this.element.removeEventListener('leafletjs:connect', this._onConnect);
    }

    _onConnect(event) {
        // The map was just created
        console.log(event.detail.map); // You can access the map instance using the event details
        console.log(event.detail.layer); // You can access the default layer instance using the event details

        // For instance you can add additional markers to the map
        new LLMarker([lat, lon]).addTo(event.detail.map);

        // Changing the default URL for the default layer
        event.detail.layer.setUrl('http://{s}.somedomain.com/blabla/{z}/{x}/{y}{r}.png');
    }
}
```

Then in your render call, add your controller as an HTML attribute:

```twig
{{ render_map(map, {'data-controller': 'mymap'}) }}
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
