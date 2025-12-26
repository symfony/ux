# CHANGELOG

## 2.32

-  Add `Map::removeAllMarkers()`, `Map::removeAllPolygons()`, `Map::removeAllPolylines()`, `Map::removeAllCircles()` and `Map::removeAllRectangles()` methods

## 2.31

-  Add `fitBoundsToMarkers` parameter to `ux_map()` Twig function

## 2.30

-  Ensure compatibility with PHP 8.5
-  Deprecate option `title` from `Polygon`, `Polyline`, `Rectangle` and `Circle` in favor of `infoWindow`

## 2.29.0

-  Add Symfony 8 support
-  Add `Cluster` class and `ClusteringAlgorithmInterface` with two implementations `GridClusteringAlgorithm` and `MortonClusteringAlgorithm`

## 2.28

-  Add `minZoom` and `maxZoom` options to `Map` to set the minimum and maximum zoom levels

## 2.27

-   The `fitBoundsToMarkers` option is not overridden anymore when using the `Map` LiveComponent, but now respects the value you defined.
    You may encounter unwanted behavior when adding/removing elements to the map.
    To use the previous behavior, you must call `$this->getMap()->fitBoundsToMarkers(false)` in your LiveComponent's live actions

-   Add support for creating `Circle` by passing a `Point` and a radius (in meters) to the `Circle` constructor, e.g.:
```php
$map->addCircle(new Circle(
    center: new Point(48.856613, 2.352222), // Paris
    radius: 5_000 // 5km
));
```

-   Add support for creating `Rectangle` by passing two `Point` instances to the `Rectangle` constructor, e.g.:
```php
$map->addRectangle(new Rectangle(
    southWest: new Point(48.856613, 2.352222), // Paris
    northEast: new Point(48.51238 2.21080) // Gare de Lyon (Paris)
));
```

-   Deprecate property `rawOptions` from `ux:map:*:before-create` events, in favor of `bridgeOptions` instead.
-   Map options can now be configured and overridden through the `ux:map:pre-connect` event:
```js
this.element.addEventListener('ux:map:pre-connect', (event) => {
    // Override the map center and zoom
    event.detail.zoom = 10;
    event.detail.center = { lat: 48.856613, lng: 2.352222 };

    // Override the normalized `*Options` PHP classes (e.g. `GoogleMapOptions` or `LeafletMapOptions`)
    console.log(event.detail.options);

    // Override the options specific to the renderer bridge (e.g. `google.maps.MapOptions` or `L.MapOptions`)
    event.detail.bridgeOptions = {
        // ...
    };
});
```
-  Add `extra` data support to `Map`, which can be accessed in `ux:map:pre-connect` and `ux:map:connect` events

## 2.26

-  Add support for creating `Polygon` with holes, by passing an array of `array<Point>` as `points` parameter to the `Polygon` constructor, e.g.:
```php
// Draw a polygon with a hole in it, on the French map
$map->addPolygon(new Polygon(points: [
    // First path, the outer boundary of the polygon
    [
        new Point(48.117266, -1.677792), // Rennes
        new Point(50.629250, 3.057256), // Lille
        new Point(48.573405, 7.752111), // Strasbourg
        new Point(43.296482, 5.369780), // Marseille
        new Point(44.837789, -0.579180), // Bordeaux
    ],
    // Second path, it will make a hole in the previous one
    [
        new Point(45.833619, 1.261105), // Limoges
        new Point(45.764043, 4.835659), // Lyon
        new Point(49.258329, 4.031696), // Reims
        new Point(48.856613, 2.352222), // Paris
    ],
]));
```

## 2.25

-  Downgrade PHP requirement from 8.3 to 8.1

## 2.24

-  Installing the package in a Symfony app using Flex won't add the `@symfony/ux-map` dependency to the `package.json` file anymore.
-  Add `Icon` to customize a `Marker` icon (URL or SVG content)
-  Add parameter `id` to `Marker`, `Polygon` and `Polyline` constructors
-  Add method `Map::removeMarker(string|Marker $markerOrId)`
-  Add method `Map::removePolygon(string|Polygon $polygonOrId)`
-  Add method `Map::removePolyline(string|Polyline $polylineOrId)`

## 2.23

-  Add `DistanceUnit` to represent distance units (`m`, `km`, `miles`, `nmi`) and
   ease conversion between units.
-  Add `DistanceCalculatorInterface` interface and three implementations:
   `HaversineDistanceCalculator`, `SphericalCosineDistanceCalculator` and `VincentyDistanceCalculator`.
-  Add `CoordinateUtils` helper, to convert decimal coordinates (`43.2109`) in DMS (`56° 78' 90"`)

## 2.22

-   Add method `Symfony\UX\Map\Renderer\AbstractRenderer::tapOptions()`, to allow Renderer to modify options before rendering a Map.
-   Add `ux_map.google_maps.default_map_id` configuration to set the Google ``Map ID``
-   Add `ComponentWithMapTrait` to ease maps integration in [Live Components](https://symfony.com/bundles/ux-live-component/current/index.html)
-   Add `Polyline` support

## 2.20

-   Deprecate `render_map` Twig function (will be removed in 2.21). Use
    `ux_map` or the `<twig:ux:map />` Twig component instead.
-   Add `ux_map` Twig function (replaces `render_map` with a more flexible
    interface)
-   Add `<twig:ux:map />` Twig component
-   The importmap entry `@symfony/ux-map/abstract-map-controller` can be removed
    from your importmap, it is no longer needed.
-   Add `Polygon` support

## 2.19

-   Component added
