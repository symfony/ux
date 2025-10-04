# CHANGELOG

## 2.31

-  Display a warning when trying to define `bridgeOptions.icon` for a `Marker` that already has an `Icon`

## 2.30

-  Ensure compatibility with PHP 8.5

## 2.29.0

-  Add Symfony 8 support

## 2.27

-  Add `attributionControl` and `attributionControlOptions` to `LeafletOptions`,
   to configure [attribution control](https://leafletjs.com/reference.html#map-attributioncontrol) and its options
-  Add `zoomControl` and `zoomControlOptions` to `LeafletOptions`,
   to configure [zoom control](https://leafletjs.com/reference.html#map-zoomcontrol) and its options

## 2.26

-  Using `new LeafletOptions(tileLayer: false)` will now disable the default `TileLayer`.
   Useful when using a custom tiles layer rendering engine not configurable with `L.tileLayer().addTo(map)` method
   (e.g.: [Esri/esri-leaflet-vector](https://github.com/Esri/esri-leaflet-vector))

## 2.25

-  Downgrade PHP requirement from 8.3 to 8.1

## 2.20

### BC Breaks

-   Renamed importmap entry `@symfony/ux-leaflet-map/map-controller` to `@symfony/ux-leaflet-map`,
    you will need to update your importmap.

## 2.19

-   Bridge added
