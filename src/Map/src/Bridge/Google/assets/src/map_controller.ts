/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import AbstractMapController from '@symfony/ux-map';
import type { Point, MarkerDefinition } from '@symfony/ux-map';
import type { LoaderOptions } from '@googlemaps/js-api-loader';
import { Loader } from '@googlemaps/js-api-loader';

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

export default class extends AbstractMapController<
    MapOptions,
    google.maps.Map,
    google.maps.marker.AdvancedMarkerElement,
    google.maps.InfoWindow
> {
    static values = {
        providerOptions: Object,
    };

    declare providerOptionsValue: Pick<
        LoaderOptions,
        'apiKey' | 'id' | 'language' | 'region' | 'nonce' | 'retries' | 'url' | 'version' | 'libraries'
    >;

    async connect() {
        if (!_google) {
            _google = { maps: {} };

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
                const library = libraries[index];

                // The following libraries are in a sub-namespace
                if (['marker', 'places', 'geometry', 'journeySharing', 'drawing', 'visualization'].includes(library)) {
                    _google.maps[library] = libraryImplementation;
                } else {
                    _google.maps = { ..._google.maps, ...libraryImplementation };
                }
            });
        }

        super.connect();
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

    protected doCreateMarker(
        definition: MarkerDefinition<google.maps.marker.AdvancedMarkerElementOptions, google.maps.InfoWindowOptions>
    ): google.maps.marker.AdvancedMarkerElement {
        const { position, title, infoWindow, extra, rawOptions = {}, ...otherOptions } = definition;

        const marker = new _google.maps.marker.AdvancedMarkerElement({
            position,
            title,
            ...otherOptions,
            ...rawOptions,
            map: this.map,
        });

        if (infoWindow) {
            if (infoWindow.opened) {
                this.createInfoWindow({ definition: infoWindow, marker });
            } else {
                marker.addListener('click', () => {
                    this.createInfoWindow({ definition: infoWindow, marker, onMarkerClick: true });
                });
            }
        }

        return marker;
    }

    protected doCreateInfoWindow({
        definition,
        marker,
        onMarkerClick,
    }: {
        definition: MarkerDefinition<
            google.maps.marker.AdvancedMarkerElementOptions,
            google.maps.InfoWindowOptions
        >['infoWindow'];
        marker: google.maps.marker.AdvancedMarkerElement;
        onMarkerClick: boolean;
    }): google.maps.InfoWindow {
        const { headerContent, content, extra, rawOptions = {}, ...otherOptions } = definition;

        const infoWindow = new _google.maps.InfoWindow({
            headerContent: this.createTextOrElement(headerContent),
            content: this.createTextOrElement(content),
            ...otherOptions,
            ...rawOptions,
        });

        if (definition.opened || onMarkerClick) {
            infoWindow.open({
                map: this.map,
                shouldFocus: false,
                anchor: marker,
            });
        }

        if (onMarkerClick) {
            if (definition.autoClose) {
                this.closeInfoWindowsExcept(infoWindow);
            }
        }

        return infoWindow;
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

    private closeInfoWindowsExcept(infoWindow: google.maps.InfoWindow) {
        this.infoWindows.forEach((otherInfoWindow) => {
            if (otherInfoWindow !== infoWindow) {
                otherInfoWindow.close();
            }
        });
    }

    protected doFitBoundsToMarkers(): void {
        if (this.markers.length === 0) {
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
}
