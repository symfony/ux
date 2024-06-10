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

## Resources

- [Documentation](https://symfony.com/bundles/ux-map/current/index.html)
- [Report issues](https://github.com/symfony/ux/issues) and
  [send Pull Requests](https://github.com/symfony/ux/pulls)
  in the [main Symfony UX repository](https://github.com/symfony/ux)
