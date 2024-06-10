# Symfony UX Map: Google Maps

[Google Maps](https://developers.google.com/maps/documentation/javascript/overview) integration for Symfony UX Map.

## DSN example

```dotenv
UX_MAP_DSN=google://GOOGLE_MAPS_API_KEY@default

# With options
UX_MAP_DSN=google://GOOGLE_MAPS_API_KEY@default?version=weekly
UX_MAP_DSN=google://GOOGLE_MAPS_API_KEY@default?language=fr&region=FR
```

Available options:

| Option     | Description                                                                                                                        | Default                                   |
|------------|------------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------|
| `id`       | The id of the script tag                                                                                                           | `__googleMapsScriptId`                    |
| `language` | Force language, see [list of supported languages](https://developers.google.com/maps/faq#languagesupport) specified in the browser | The user's preferred language             |
| `region`   | Unicode region subtag identifiers compatible with [ISO 3166-1](https://en.wikipedia.org/wiki/ISO_3166-1)                           |                                           |
| `nonce`    | Use a cryptographic nonce attribute                                                                                                |                                           |
| `retries`  | The number of script load retries                                                                                                  | 3                                         |
| `url`      | Custom url to load the Google Maps API script                                                                                      | `https://maps.googleapis.com/maps/api/js` |
| `version`  | The release channels or version numbers                                                                                            | `weekly`                                  |

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

## Resources

- [Documentation](https://symfony.com/bundles/ux-map/current/index.html)
- [Report issues](https://github.com/symfony/ux/issues) and
  [send Pull Requests](https://github.com/symfony/ux/pulls)
  in the [main Symfony UX repository](https://github.com/symfony/ux)
