# Symfony UX Map: Leaflet

[Leaflet](https://leafletjs.com/) integration for Symfony UX Map.

## Installation

Install the bridge using Composer and Symfony Flex:

```shell
composer require symfony/ux-leaflet-map
```

If you're using WebpackEncore, install your assets and restart Encore (not
needed if you're using AssetMapper):

```shell
npm install --force
npm run watch
```

> [!NOTE]
> Alternatively, [@symfony/ux-leaflet-map package](https://www.npmjs.com/package/@symfony/ux-leaflet-map) can be used to install the JavaScript assets without requiring PHP.

## DSN example

```dotenv
UX_MAP_DSN=leaflet://default
```

## Map options

You can use the `LeafletOptions` class to configure your `Map`::

```php
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\AttributionControlOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\ControlPosition;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;
use Symfony\UX\Map\Bridge\Leaflet\Option\ZoomControlOptions;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Map;

$map = (new Map())
    ->center(new Point(48.8566, 2.3522))
    ->zoom(6);

$leafletOptions = (new LeafletOptions())
    ->tileLayer(new TileLayer(
        url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        options: [
            'minZoom' => 5,
            'maxZoom' => 10,
        ]
    ))
    ->attributionControl(false)
    ->attributionControlOptions(new AttributionControlOptions(ControlPosition::BOTTOM_LEFT))
    ->zoomControl(false)
    ->zoomControlOptions(new ZoomControlOptions(ControlPosition::TOP_LEFT))
;

// Add the custom options to the map
$map->options($leafletOptions);
```

## Use cases

Below are some common or advanced use cases when using a map.

### Customize the marker

A common use case is to customize the marker. You can listen to the `ux:map:marker:before-create` event to customize the marker before it is created.

Assuming you have a map with a custom controller:

```twig
{{ ux_map(map, {'data-controller': 'my-map' }) }}
```

You can create a Stimulus controller to customize the markers before they are created:

```js
// assets/controllers/my_map_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.element.addEventListener('ux:map:marker:before-create', this._onMarkerBeforeCreate);
    }

    disconnect() {
        // Always remove listeners when the controller is disconnected
        this.element.removeEventListener('ux:map:marker:before-create', this._onMarkerBeforeCreate);
    }

    _onMarkerBeforeCreate(event) {
        // You can access the marker definition and the Leaflet object
        // Note: `definition.bridgeOptions` is the raw options object that will be passed to the `L.marker` constructor.
        const { definition, L } = event.detail;

        // Use a custom icon for the marker
        const redIcon = L.icon({
            // Note: instead of using a hardcoded URL, you can use the `extra` parameter from `new Marker()` (PHP) and access it here with `definition.extra`.
            iconUrl: 'https://leafletjs.com/examples/custom-icons/leaf-red.png',
            shadowUrl: 'https://leafletjs.com/examples/custom-icons/leaf-shadow.png',
            iconSize: [38, 95], // size of the icon
            shadowSize: [50, 64], // size of the shadow
            iconAnchor: [22, 94], // point of the icon which will correspond to marker's location
            shadowAnchor: [4, 62], // the same for the shadow
            popupAnchor: [-3, -76], // point from which the popup should open relative to the iconAnchor
        });

        definition.bridgeOptions = {
            icon: redIcon,
        };
    }
}
```

### Disable the default tile layer

If you need to use a custom tiles layer rendering engine that is not compatible with the `L.tileLayer().addTo(map)` method
(e.g. [Esri/esri-leaflet-vector](https://github.com/Esri/esri-leaflet-vector)), you can disable the default tile layer by passing `tileLayer: false` to the `LeafletOptions`:

```php
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;

$leafletOptions = new LeafletOptions(tileLayer: false);
// or
$leafletOptions = (new LeafletOptions())
    ->tileLayer(false);
```

## Leaflet 2.x support

The Leaflet bridge supports **both Leaflet 1.9.x and 2.x** peer installations. Version detection happens once, at controller connect time — there is no build-time flag and no separate bundle.

### What changed in Leaflet 2

Leaflet 2 dropped the lowercase factory functions (`L.map()`, `L.marker()`, `L.tileLayer()`, `L.icon()`, etc.) in favor of named class exports (`new Map()`, `new Marker()`, etc.) and stopped exporting a default global `L` namespace.

### Preserved consumer contract

Userland code listening to `ux:map:*:before-create` and `ux:map:*:after-create` events receives the Leaflet namespace as `event.detail.L`. **This contract is preserved on both versions.** Under v2, the bridge synthesizes a v1-flavored namespace from the v2 named class exports so that existing code such as:

```js
_onMarkerBeforeCreate(event) {
    const { definition, L } = event.detail;
    const redIcon = L.icon({ iconUrl: '...', iconSize: [38, 95] });
    definition.bridgeOptions = { icon: redIcon };
}
```

keeps working without any edit. The synthesized namespace is constructed from the v2 named imports only — no runtime `eval`, no global mutation.

### Installing Leaflet 2

Bump the `leaflet` peer dependency in your own `package.json`; the controller will detect it on the next page load:

```json
{
    "dependencies": {
        "leaflet": "^2.0.0"
    }
}
```

CSS: Leaflet 2 ships `dist/leaflet.css` on both npm and jsDelivr (no minified variant). See Known Issues below for the alias workaround that applies to both versions.

## Known issues

### Unable to find `leaflet/dist/leaflet.min.css`

The Stimulus controller references `leaflet/dist/leaflet.min.css` — a path that exists on [jsDelivr](https://www.jsdelivr.com/package/npm/leaflet) for Leaflet 1.9.x (used by the Symfony AssetMapper component) but does **not** exist in the Leaflet 1.9 [npm package](https://www.npmjs.com/package/leaflet), and **not at all** in the Leaflet 2.x package (v2 ships only `leaflet.css` on both npm and jsDelivr).

The correct path is `leaflet/dist/leaflet.css`, but it is not possible to change the bundled import because it would break compatibility with the AssetMapper + Leaflet 1.9 combination.

#### Webpack Encore

When using Webpack Encore with the Leaflet bridge, you may encounter the following error:

```
Module build failed: Module not found:
"./node_modules/.pnpm/file+vendor+symfony+ux-leaflet-map+assets_@hotwired+stimulus@3.0.0_leaflet@1.9.4/node_modules/@symfony/ux-leaflet-map/dist/map_controller.js" contains a reference to the file "leaflet/dist/leaflet.min.css".
This file can not be found, please check it for typos or update it if the file got moved.

Entrypoint app = runtime.67292354.js 488.0777101a.js app.b75294ae.css app.0975a86d.js
webpack compiled with 1 error
 ELIFECYCLE  Command failed with exit code 1.
```

As a workaround, you can configure Webpack Encore to add an alias for the `leaflet/dist/leaflet.min.css` file. This works for both Leaflet 1.9 and 2:

```js
Encore.addAliases({
    'leaflet/dist/leaflet.min.css': 'leaflet/dist/leaflet.css',
});
```

#### AssetMapper on Leaflet 2

If you install Leaflet 2 and use AssetMapper, pin the CSS entry in your `importmap.php` to the un-minified file:

```php
'leaflet/dist/leaflet.min.css' => [
    'version' => '2.0.0',
    'type' => 'css',
    'url' => 'https://cdn.jsdelivr.net/npm/leaflet@2.0.0/dist/leaflet.css',
],
```

## Resources

- [Documentation](https://symfony.com/bundles/ux-map/current/index.html)
- [Report issues](https://github.com/symfony/ux/issues) and
  [send Pull Requests](https://github.com/symfony/ux/pulls)
  in the [main Symfony UX repository](https://github.com/symfony/ux)
