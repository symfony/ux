# CHANGELOG

## 3.0.0

-   Minimum required Symfony version is now 6.4
-   Minimum required PHP version is now 8.2

## 2.30

- Ensure compatibility with PHP 8.5

## 2.29.0

-  Add Symfony 8 support

## 2.27

-   Fix `InfoWindow` compatibility with new `Circle` and `Rectangle` supported elements.

    This fix led to a refactoring that can impact the `InfoWindow` position when used with `Polygon` or `Polyline`,
    because their shapes are too complex.
    Instead, listen to the `ux:map:info-window:before-create` event to set the position manually.
    ```js
    this.element.addEventListener('ux:map:info-window:before-create', (event) => {
        const { google, element, definition } = event.detail;

        if (element instanceof google.maps.Polygon) {
            // Set the position to the center of the polygon
            const bounds = new google.maps.LatLngBounds();

            element.getPath().forEach((latLng) => bounds.extend(latLng));
            definition.infoWindow.bridgeOptions.position = bounds.getCenter();
        }
    });
    ```

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
