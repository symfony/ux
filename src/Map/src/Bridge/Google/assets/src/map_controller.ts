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
import AbstractMapController, { IconTypes } from '@symfony/ux-map';
import type {
    Icon,
    InfoWindowWithoutPositionDefinition,
    MarkerDefinition,
    Point,
    PolygonDefinition,
    PolylineDefinition,
} from '@symfony/ux-map';

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
    google.maps.Polyline
> {
    declare providerOptionsValue: Pick<
        LoaderOptions,
        'apiKey' | 'id' | 'language' | 'region' | 'nonce' | 'retries' | 'url' | 'version' | 'libraries'
    >;

    declare map: google.maps.Map;

    public parser: DOMParser;

    async connect() {
        if (!_google) {
            _google = { maps: {} as typeof google.maps };

            let { libraries = [], ...loaderOptions } = this.providerOptionsValue;

            const loader = new Loader(loaderOptions);

            // We could have used `loader.load()` to correctly load libraries, but this method is deprecated in favor of `loader.importLibrary()`.
            // But `loader.importLibrary()` is not a 1-1 replacement for `loader.load()`, we need to re-build the `google.maps` object ourselves,
            // see https://github.com/googlemaps/js-api-loader/issues/837 for more information.
            libraries = ['core', ...libraries.filter((library) => library !== 'core')]; // Ensure 'core' is loaded first
            const librariesImplementations = await Promise.all(
                libraries.map((library) => loader.importLibrary(library))
            );
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
        }

        super.connect();
        this.parser = new DOMParser();
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

    protected doCreateMap({
        center,
        zoom,
        options,
    }: {
        center: Point | null;
        zoom: number | null;
        options: MapOptions;
    }): google.maps.Map {
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

    protected doCreateInfoWindow({
        definition,
        element,
    }: {
        definition: InfoWindowWithoutPositionDefinition<google.maps.InfoWindowOptions>;
        element: google.maps.marker.AdvancedMarkerElement | google.maps.Polygon | google.maps.Polyline;
    }): google.maps.InfoWindow {
        const { headerContent, content, extra, rawOptions = {}, ...otherOptions } = definition;

        const infoWindow = new _google.maps.InfoWindow({
            headerContent: this.createTextOrElement(headerContent),
            content: this.createTextOrElement(content),
            ...otherOptions,
            ...rawOptions,
        });

        if (element instanceof google.maps.marker.AdvancedMarkerElement) {
            element.addListener('click', () => {
                if (definition.autoClose) {
                    this.closeInfoWindowsExcept(infoWindow);
                }
                infoWindow.open({ map: this.map, anchor: element });
            });

            if (definition.opened) {
                infoWindow.open({ map: this.map, anchor: element });
            }
        } else if (element instanceof google.maps.Polygon) {
            element.addListener('click', (event: any) => {
                if (definition.autoClose) {
                    this.closeInfoWindowsExcept(infoWindow);
                }
                infoWindow.setPosition(event.latLng);
                infoWindow.open(this.map);
            });

            if (definition.opened) {
                const bounds = new google.maps.LatLngBounds();
                element.getPath().forEach((point: google.maps.LatLng) => {
                    bounds.extend(point);
                });
                infoWindow.setPosition(bounds.getCenter());
                infoWindow.open({ map: this.map, anchor: element });
            }
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

    protected doCreateIcon({
        definition,
        element,
    }: {
        definition: Icon;
        element: google.maps.marker.AdvancedMarkerElement;
    }): void {
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
