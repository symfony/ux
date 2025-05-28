# CHANGELOG

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
