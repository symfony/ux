# Symfony UX Map: Google Maps

[Google Maps](https://developers.google.com/maps/documentation/javascript/overview) integration for Symfony UX Map.

## DSN example

```dotenv
UX_MAP_DSN=google://GOOGLE_MAPS_API_KEY@default

# With options
UX_MAP_DSN=google://GOOGLE_MAPS_API_KEY@default?version=weekly
UX_MAP_DSN=google://GOOGLE_MAPS_API_KEY@default?language=fr&region=FR
UX_MAP_DSN=google://GOOGLE_MAPS_API_KEY@default??libraries[]=geometry&libraries[]=places
```

Available options:

| Option      | Description                                                                                                                        | Default                                                     |
|-------------|------------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------|
| `id`        | The id of the script tag                                                                                                           | `__googleMapsScriptId`                                      |
| `language`  | Force language, see [list of supported languages](https://developers.google.com/maps/faq#languagesupport) specified in the browser | The user's preferred language                               |
| `region`    | Unicode region subtag identifiers compatible with [ISO 3166-1](https://en.wikipedia.org/wiki/ISO_3166-1)                           |                                                             |
| `nonce`     | Use a cryptographic nonce attribute                                                                                                |                                                             |
| `retries`   | The number of script load retries                                                                                                  | 3                                                           |
| `url`       | Custom url to load the Google Maps API script                                                                                      | `https://maps.googleapis.com/maps/api/js`                   |
| `version`   | The release channels or version numbers                                                                                            | `weekly`                                                    |
| `libraries` | The additional libraries to load, see [list of supported libraries](https://googlemaps.github.io/js-api-loader/types/Library.html) | `['maps', 'marker']`, those two libraries are always loaded |

## Map options

You can use the `GoogleOptions` class to configure your `Map`::

```php
use Symfony\UX\Map\Bridge\Google\GoogleOptions;
use Symfony\UX\Map\Bridge\Google\Option\ControlPosition;
use Symfony\UX\Map\Bridge\Google\Option\FullscreenControlOptions;
use Symfony\UX\Map\Bridge\Google\Option\GestureHandling;
use Symfony\UX\Map\Bridge\Google\Option\MapTypeControlOptions;
use Symfony\UX\Map\Bridge\Google\Option\MapTypeControlStyle;
use Symfony\UX\Map\Bridge\Google\Option\StreetViewControlOptions;
use Symfony\UX\Map\Bridge\Google\Option\ZoomControlOptions;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Map;

$map = (new Map())
    ->center(new Point(48.8566, 2.3522))
    ->zoom(6);

// To configure controls options, and some other options:
$googleOptions = (new GoogleOptions())
    ->mapId('YOUR_MAP_ID')
    ->gestureHandling(GestureHandling::GREEDY)
    ->backgroundColor('#f00')
    ->doubleClickZoom(true)
    ->zoomControlOptions(new ZoomControlOptions(
        position: ControlPosition::BLOCK_START_INLINE_END,
    ))
    ->mapTypeControlOptions(new MapTypeControlOptions(
        mapTypeIds: ['roadmap'],
        position: ControlPosition::INLINE_END_BLOCK_START,
        style: MapTypeControlStyle::DROPDOWN_MENU,
    ))
    ->streetViewControlOptions(new StreetViewControlOptions(
        position: ControlPosition::BLOCK_END_INLINE_START,
    ))
    ->fullscreenControlOptions(new FullscreenControlOptions(
        position: ControlPosition::INLINE_START_BLOCK_END,
    ))
;

// To disable controls:
$googleOptions = (new GoogleOptions())
    ->mapId('YOUR_MAP_ID')
    ->zoomControl(false)
    ->mapTypeControl(false)
    ->streetViewControl(false)
    ->fullscreenControl(false)
;

// Add the custom options to the map
$map->options($googleOptions);
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
        // You can access the marker definition and the google object
        // Note: `definition.rawOptions` is the raw options object that will be passed to the `google.maps.Marker` constructor. 
        const { definition, google } = event.detail;

        // 1. To use a custom image for the marker 
        const beachFlagImg = document.createElement("img");
        // Note: instead of using an hardcoded URL, you can use the `extra` parameter from `new Marker()` (PHP) and access it here with `definition.extra`.
        beachFlagImg.src = "https://developers.google.com/maps/documentation/javascript/examples/full/images/beachflag.png";
        definition.rawOptions =  { 
            content: beachFlagImg
        }
      
        // 2. To use a custom glyph for the marker
        const pinElement = new google.maps.marker.PinElement({
            // Note: instead of using an hardcoded URL, you can use the `extra` parameter from `new Marker()` (PHP) and access it here with `definition.extra`. 
            glyph: new URL('https://maps.gstatic.com/mapfiles/place_api/icons/v2/museum_pinlet.svg'), 
            glyphColor: "white",
        });
        definition.rawOptions = {
            content: pinElement.element,
        }
    }
}
```

## Resources

- [Documentation](https://symfony.com/bundles/ux-map/current/index.html)
- [Report issues](https://github.com/symfony/ux/issues) and
  [send Pull Requests](https://github.com/symfony/ux/pulls)
  in the [main Symfony UX repository](https://github.com/symfony/ux)
