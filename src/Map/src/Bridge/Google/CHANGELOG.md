# CHANGELOG

## 2.25

-  Downgrade PHP requirement from 8.3 to 8.1

## 2.22

-   Add support for configuring a default Map ID
-   Add argument `$defaultMapId` to `Symfony\UX\Map\Bridge\Google\Renderer\GoogleRendererFactory` constructor
-   Add argument `$defaultMapId` to `Symfony\UX\Map\Bridge\Google\Renderer\GoogleRenderer` constructor

## 2.20

### BC Breaks

-   Renamed importmap entry `@symfony/ux-google-map/map-controller` to `@symfony/ux-google-map`,
    you will need to update your importmap.

## 2.19

-   Bridge added
