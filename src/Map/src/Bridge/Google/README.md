# Symfony UX Map: Google Maps

[Google Maps](https://developers.google.com/maps/documentation/javascript/overview) integration for Symfony UX Map.

## Installation

Install the bridge using Composer and Symfony Flex:

```shell
composer require symfony/ux-google-map
```

If you're using WebpackEncore, install your assets and restart Encore (not
needed if you're using AssetMapper):

```shell
npm install --force
npm run watch
```

> [!NOTE]
> Alternatively, [@symfony/ux-google-map package](https://www.npmjs.com/package/@symfony/ux-google-map) can be used to install the JavaScript assets without requiring PHP.

## DSN example

```dotenv
UX_MAP_DSN=google://GOOGLE_MAPS_API_KEY@default

# With
UX_MAP_DSN=google://GOOGLE_MAPS_API_KEY@default?v=weekly
UX_MAP_DSN=google://GOOGLE_MAPS_API_KEY@default?language=fr&region=FR
UX_MAP_DSN=google://GOOGLE_MAPS_API_KEY@default?libraries[]=geometry&libraries[]=places
```

Available options:

| Option               | Description                                                                                                                                                            | Default                                                     |
|----------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------|
| `v`                  | The version of the Maps JavaScript API to load, could be a [release channels](https://developers.google.com/maps/documentation/javascript/versions) or version numbers | `weekly`                                                    |
| `language`           | The language to use, see [list of supported languages](https://developers.google.com/maps/faq#languagesupport) specified in the browser                                | The user's preferred language                               |
| `region`             | The region code to use, unicode region subtag identifiers compatible with [ISO 3166-1](https://en.wikipedia.org/wiki/ISO_3166-1)                                       |                                                             |
| `libraries`          | Additional libraries to load, see [list of supported libraries](https://developers.google.com/maps/documentation/javascript/libraries#available-libraries)             | `['maps', 'marker']`, those two libraries are always loaded |
| `authReferrerPolicy` | Set the referrer policy for the API requests                                                                                                                           |                                                             |
| `mapIds`             | An array of map IDs to preload                                                                                                                                         |                                                             |
| `channel`            | Can be used to track your usage                                                                                                                                        |                                                             |
| `solutionChannel`    | Used by the Google Maps Platform to track adoption and usage of examples and solutions                                                                                 |                                                             |

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

// To configure control options and other map options:
$googleOptions = (new GoogleOptions())
    // You can skip this option if you configure "ux_map.google_maps.default_map_id"
    // in your "config/packages/ux_map.yaml".
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
        // Note: `definition.bridgeOptions` is the raw options object that will be passed to the `google.maps.Marker` constructor.
        const { definition, google } = event.detail;

        // 1. To use a custom image for the marker
        const beachFlagImg = document.createElement("img");
        // Note: instead of using a hardcoded URL, you can use the `extra` parameter from `new Marker()` (PHP) and access it here with `definition.extra`.
        beachFlagImg.src = "https://developers.google.com/maps/documentation/javascript/examples/full/images/beachflag.png";
        definition.bridgeOptions =  {
            content: beachFlagImg
        }

        // 2. To use a custom glyph for the marker
        const pinElement = new google.maps.marker.PinElement({
            // Note: instead of using a hardcoded URL, you can use the `extra` parameter from `new Marker()` (PHP) and access it here with `definition.extra`.
            glyph: new URL('https://maps.gstatic.com/mapfiles/place_api/icons/v2/museum_pinlet.svg'),
            glyphColor: "white",
        });
        definition.bridgeOptions = {
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
