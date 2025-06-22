/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import type { LoaderOptions } from '@googlemaps/js-api-loader';
import { Loader } from '@googlemaps/js-api-loader';
import type {
    CircleDefinition,
    Icon,
    InfoWindowWithoutPositionDefinition,
    MarkerDefinition,
    Point,
    PolygonDefinition,
    PolylineDefinition,
    RectangleDefinition,
} from '@symfony/ux-map';
import AbstractMapController, { IconTypes } from '@symfony/ux-map';

type MapOptions = Pick<
    google.maps.MapOptions,
    | 'mapId'
    | 'gestureHandling'
    | 'backgroundColor'
    | 'disableDoubleClickZoom'
    | 'zoomControl'
    | 'zoomControlOptions'
    | 'mapTypeControl'
    | 'mapTypeControlOptions'
    | 'streetViewControl'
    | 'streetViewControlOptions'
    | 'fullscreenControl'
    | 'fullscreenControlOptions'
>;

let _google: typeof google;

// Loading the Google Maps API is an asynchronous operation, so we need to track the loading state to prevent race conditions.
let _loading = false;
let _loaded = false;
let _onLoadedCallbacks: Array<() => void> = [];

const parser = new DOMParser();

export default class extends AbstractMapController<
    MapOptions,
    google.maps.Map,
    google.maps.marker.AdvancedMarkerElementOptions,
    google.maps.marker.AdvancedMarkerElement,
    google.maps.InfoWindowOptions,
    google.maps.InfoWindow,
    google.maps.PolygonOptions,
    google.maps.Polygon,
    google.maps.PolylineOptions,
    google.maps.Polyline,
    google.maps.CircleOptions,
    google.maps.Circle,
    google.maps.RectangleOptions,
    google.maps.Rectangle
> {
    declare providerOptionsValue: Pick<LoaderOptions, 'apiKey' | 'id' | 'language' | 'region' | 'nonce' | 'retries' | 'url' | 'version' | 'libraries'>;

    declare map: google.maps.Map;

    public parser: DOMParser;

    async connect() {
        const onLoaded = () => super.connect();

        if (_loaded) {
            onLoaded();
            return;
        }

        if (_loading) {
            _onLoadedCallbacks.push(onLoaded);
            return;
        }

        _loading = true;
        _google = { maps: {} as typeof google.maps };

        let { libraries = [], ...loaderOptions } = this.providerOptionsValue;

        const loader = new Loader(loaderOptions);

        // We could have used `loader.load()` to correctly load libraries, but this method is deprecated in favor of `loader.importLibrary()`.
        // But `loader.importLibrary()` is not a 1-1 replacement for `loader.load()`, we need to re-build the `google.maps` object ourselves,
        // see https://github.com/googlemaps/js-api-loader/issues/837 for more information.
        libraries = ['core', ...libraries.filter((library) => library !== 'core')]; // Ensure 'core' is loaded first
        const librariesImplementations = await Promise.all(libraries.map((library) => loader.importLibrary(library)));
        librariesImplementations.map((libraryImplementation, index) => {
            if (typeof libraryImplementation !== 'object' || libraryImplementation === null) {
                return;
            }

            const library = libraries[index];

            // The following libraries are in a sub-namespace
            if (['marker', 'places', 'geometry', 'journeySharing', 'drawing', 'visualization'].includes(library)) {
                // @ts-ignore
                _google.maps[library] = libraryImplementation as any;
            } else {
                _google.maps = { ..._google.maps, ...libraryImplementation };
            }
        });

        _loading = false;
        _loaded = true;
        onLoaded();
        _onLoadedCallbacks.forEach((callback) => callback());
        _onLoadedCallbacks = [];
    }

    public centerValueChanged(): void {
        if (this.map && this.hasCenterValue && this.centerValue) {
            this.map.setCenter(this.centerValue);
        }
    }

    public zoomValueChanged(): void {
        if (this.map && this.hasZoomValue && this.zoomValue) {
            this.map.setZoom(this.zoomValue);
        }
    }

    protected dispatchEvent(name: string, payload: Record<string, unknown> = {}): void {
        this.dispatch(name, {
            prefix: 'ux:map',
            detail: {
                ...payload,
                google: _google,
            },
        });
    }

    protected doCreateMap({ center, zoom, options }: { center: Point | null; zoom: number | null; options: MapOptions }): google.maps.Map {
        // We assume the following control options are enabled if their options are set
        options.zoomControl = typeof options.zoomControlOptions !== 'undefined';
        options.mapTypeControl = typeof options.mapTypeControlOptions !== 'undefined';
        options.streetViewControl = typeof options.streetViewControlOptions !== 'undefined';
        options.fullscreenControl = typeof options.fullscreenControlOptions !== 'undefined';

        return new _google.maps.Map(this.element, {
            ...options,
            center,
            zoom,
        });
    }

    protected doCreateMarker({
        definition,
    }: {
        definition: MarkerDefinition<google.maps.marker.AdvancedMarkerElementOptions, google.maps.InfoWindowOptions>;
    }): google.maps.marker.AdvancedMarkerElement {
        const { '@id': _id, position, title, infoWindow, icon, extra, rawOptions = {}, ...otherOptions } = definition;

        const marker = new _google.maps.marker.AdvancedMarkerElement({
            position,
            title,
            ...otherOptions,
            ...rawOptions,
            map: this.map,
        });

        if (infoWindow) {
            this.createInfoWindow({ definition: infoWindow, element: marker });
        }

        if (icon) {
            this.doCreateIcon({ definition: icon, element: marker });
        }

        return marker;
    }

    protected doRemoveMarker(marker: google.maps.marker.AdvancedMarkerElement): void {
        marker.map = null;
    }

    protected doCreatePolygon({
        definition,
    }: {
        definition: PolygonDefinition<google.maps.PolygonOptions, google.maps.InfoWindowOptions>;
    }): google.maps.Polygon {
        const { '@id': _id, points, title, infoWindow, rawOptions = {} } = definition;

        const polygon = new _google.maps.Polygon({
            ...rawOptions,
            paths: points,
            map: this.map,
        });

        if (title) {
            polygon.set('title', title);
        }

        if (infoWindow) {
            this.createInfoWindow({ definition: infoWindow, element: polygon });
        }

        return polygon;
    }

    protected doRemovePolygon(polygon: google.maps.Polygon) {
        polygon.setMap(null);
    }

    protected doCreatePolyline({
        definition,
    }: {
        definition: PolylineDefinition<google.maps.PolylineOptions, google.maps.InfoWindowOptions>;
    }): google.maps.Polyline {
        const { '@id': _id, points, title, infoWindow, rawOptions = {} } = definition;

        const polyline = new _google.maps.Polyline({
            ...rawOptions,
            path: points,
            map: this.map,
        });

        if (title) {
            polyline.set('title', title);
        }

        if (infoWindow) {
            this.createInfoWindow({ definition: infoWindow, element: polyline });
        }

        return polyline;
    }

    protected doRemovePolyline(polyline: google.maps.Polyline): void {
        polyline.setMap(null);
    }

    protected doCreateCircle({ definition }: { definition: CircleDefinition<google.maps.CircleOptions, google.maps.InfoWindowOptions> }): google.maps.Circle {
        const { '@id': _id, center, radius, title, infoWindow, rawOptions = {} } = definition;

        const circle = new _google.maps.Circle({
            ...rawOptions,
            center,
            radius,
            map: this.map,
        });

        if (title) {
            circle.set('title', title);
        }

        if (infoWindow) {
            this.createInfoWindow({ definition: infoWindow, element: circle });
        }

        return circle;
    }

    protected doRemoveCircle(circle: google.maps.Circle): void {
        circle.setMap(null);
    }

    protected doCreateRectangle({
        definition,
    }: {
        definition: RectangleDefinition<google.maps.RectangleOptions, google.maps.InfoWindowOptions>;
    }): google.maps.Rectangle {
        const { northEast, southWest, title, infoWindow, rawOptions = {} } = definition;

        const rectangle = new _google.maps.Rectangle({
            ...rawOptions,
            bounds: new _google.maps.LatLngBounds(southWest, northEast),
            map: this.map,
        });

        if (title) {
            rectangle.set('title', title);
        }

        if (infoWindow) {
            this.createInfoWindow({ definition: infoWindow, element: rectangle });
        }

        return rectangle;
    }

    protected doRemoveRectangle(rectangle: google.maps.Rectangle): void {
        rectangle.setMap(null);
    }

    protected doCreateInfoWindow({
        definition,
        element,
    }: {
        definition: InfoWindowWithoutPositionDefinition<google.maps.InfoWindowOptions>;
        element: google.maps.marker.AdvancedMarkerElement | google.maps.Polygon | google.maps.Polyline | google.maps.Circle | google.maps.Rectangle;
    }): google.maps.InfoWindow {
        const { headerContent, content, opened, autoClose, rawOptions = {} } = definition;

        let position: google.maps.LatLng | null = null;
        if (element instanceof google.maps.Circle) {
            position = element.getCenter();
        } else if (element instanceof google.maps.Rectangle) {
            position = element.getBounds()?.getCenter() || null;
        } else if (element instanceof google.maps.Polygon || element instanceof google.maps.Polyline) {
            // Note: We do not compute the center of Polygons or Polylines here, since shapes can be complex.
            // Instead, listen to the `ux:map:polygon:before-create` or `ux:map:polyline:before-create` events to set the position manually.
            // ```js
            // const bounds = new google.maps.LatLngBounds();
            // element.getPath().forEach((latLng) => bounds.extend(latLng));
            // event.definition.infoWindow.rawOptions.position = bounds.getCenter();
            // ```
        }

        const infoWindowOptions: google.maps.InfoWindowOptions = {
            headerContent: this.createTextOrElement(headerContent),
            content: this.createTextOrElement(content),
            position,
            ...rawOptions,
        };

        const infoWindow = new _google.maps.InfoWindow(infoWindowOptions);

        element.addListener('click', (event: google.maps.MapMouseEvent) => {
            if (autoClose) {
                this.closeInfoWindowsExcept(infoWindow);
            }

            // Don't override the position if it was already set (e.g. through "rawOptions")
            if (infoWindowOptions.position === null) {
                infoWindow.setPosition(event.latLng);
            }

            infoWindow.open({ map: this.map, anchor: element });
        });

        if (opened) {
            if (autoClose) {
                this.closeInfoWindowsExcept(infoWindow);
            }

            infoWindow.open({ map: this.map, anchor: element });
        }

        return infoWindow;
    }

    protected doFitBoundsToMarkers(): void {
        if (this.markers.size === 0) {
            return;
        }

        const bounds = new google.maps.LatLngBounds();
        this.markers.forEach((marker) => {
            if (!marker.position) {
                return;
            }

            bounds.extend(marker.position);
        });

        this.map.fitBounds(bounds);
    }

    private createTextOrElement(content: string | null): string | HTMLElement | null {
        if (!content) {
            return null;
        }

        // we assume it's HTML if it includes "<"
        if (content.includes('<')) {
            const div = document.createElement('div');
            div.innerHTML = content;
            return div;
        }

        return content;
    }

    protected doCreateIcon({ definition, element }: { definition: Icon; element: google.maps.marker.AdvancedMarkerElement }): void {
        const { type, width, height } = definition;

        if (type === IconTypes.Svg) {
            element.content = parser.parseFromString(definition.html, 'image/svg+xml').documentElement;
        } else if (type === IconTypes.UxIcon) {
            element.content = parser.parseFromString(definition._generated_html, 'image/svg+xml').documentElement;
        } else if (type === IconTypes.Url) {
            const icon = document.createElement('img');
            icon.width = width;
            icon.height = height;
            icon.src = definition.url;
            element.content = icon;
        } else {
            throw new Error(`Unsupported icon type: ${type}.`);
        }
    }

    private closeInfoWindowsExcept(infoWindow: google.maps.InfoWindow) {
        this.infoWindows.forEach((otherInfoWindow) => {
            if (otherInfoWindow !== infoWindow) {
                otherInfoWindow.close();
            }
        });
    }
}
