# Symfony UX Map: Leaflet

[Leaflet](https://leafletjs.com/) integration for Symfony UX Map.

## DSN example

```dotenv
UX_MAP_DSN=leaflet://default
```

## Map options

You can use the `LeafletOptions` class to configure your `Map`::

```php
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;
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
import {Controller} from "@hotwired/stimulus";

export default class extends Controller
{
    connect() {
        this.element.addEventListener('ux:map:marker:before-create', this._onMarkerBeforeCreate);
    }

    disconnect() {
        // Always remove listeners when the controller is disconnected
        this.element.removeEventListener('ux:map:marker:before-create', this._onMarkerBeforeCreate);
    }

    _onMarkerBeforeCreate(event) {
        // You can access the marker definition and the Leaflet object
        // Note: `definition.rawOptions` is the raw options object that will be passed to the `L.marker` constructor. 
        const { definition, L } = event.detail;

        // Use a custom icon for the marker
        const redIcon = L.icon({
          // Note: instead of using an hardcoded URL, you can use the `extra` parameter from `new Marker()` (PHP) and access it here with `definition.extra`.
          iconUrl: 'https://leafletjs.com/examples/custom-icons/leaf-red.png',
          shadowUrl: 'https://leafletjs.com/examples/custom-icons/leaf-shadow.png',
          iconSize: [38, 95], // size of the icon
          shadowSize: [50, 64], // size of the shadow
          iconAnchor: [22, 94], // point of the icon which will correspond to marker's location
          shadowAnchor: [4, 62],  // the same for the shadow
          popupAnchor: [-3, -76] // point from which the popup should open relative to the iconAnchor
        })
  
        definition.rawOptions = {
          icon: redIcon,
        }
    }
}
```

## Known issues

### Unable to find `leaflet/dist/leaflet.min.css` file when using Webpack Encore

When using Webpack Encore with the Leaflet bridge, you may encounter the following error:
```
Module build failed: Module not found:
"./node_modules/.pnpm/file+vendor+symfony+ux-leaflet-map+assets_@hotwired+stimulus@3.0.0_leaflet@1.9.4/node_modules/@symfony/ux-leaflet-map/dist/map_controller.js" contains a reference to the file "leaflet/dist/leaflet.min.css".
This file can not be found, please check it for typos or update it if the file got moved.

Entrypoint app = runtime.67292354.js 488.0777101a.js app.b75294ae.css app.0975a86d.js
webpack compiled with 1 error
 ELIFECYCLE  Command failed with exit code 1.
```

That's because the Leaflet's Stimulus controller references the `leaflet/dist/leaflet.min.css` file, 
which exists on [jsDelivr](https://www.jsdelivr.com/package/npm/leaflet) (used by the Symfony AssetMapper component),
but does not in the [`leaflet` npm package](https://www.npmjs.com/package/leaflet).
The correct path is `leaflet/dist/leaflet.css`, but it is not possible to fix it because it would break compatibility 
with the Symfony AssetMapper component.

As a workaround, you can configure Webpack Encore to add an alias for the `leaflet/dist/leaflet.min.css` file:
```js
Encore.addAliases({
  'leaflet/dist/leaflet.min.css': 'leaflet/dist/leaflet.css',
})
```

## Resources

- [Documentation](https://symfony.com/bundles/ux-map/current/index.html)
- [Report issues](https://github.com/symfony/ux/issues) and
  [send Pull Requests](https://github.com/symfony/ux/pulls)
  in the [main Symfony UX repository](https://github.com/symfony/ux)
