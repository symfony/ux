/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import AbstractMapController from '@symfony/ux-map/abstract-map-controller';
import type { Point, MarkerDefinition } from '@symfony/ux-map/abstract-map-controller';
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

let loader: Loader;
let library: {
    _Map: typeof google.maps.Map;
    AdvancedMarkerElement: typeof google.maps.marker.AdvancedMarkerElement;
    InfoWindow: typeof google.maps.InfoWindow;
};

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
        'apiKey' | 'id' | 'language' | 'region' | 'nonce' | 'retries' | 'url' | 'version'
    >;

    async connect() {
        if (!loader) {
            loader = new Loader(this.providerOptionsValue);
        }

        const { Map: _Map, InfoWindow } = await loader.importLibrary('maps');
        const { AdvancedMarkerElement } = await loader.importLibrary('marker');
        library = { _Map, AdvancedMarkerElement, InfoWindow };

        super.connect();
    }

    protected doCreateMap({
        center,
        zoom,
        options,
    }: {
        center: Point;
        zoom: number;
        options: MapOptions;
    }): google.maps.Map {
        // We assume the following control options are enabled if their options are set
        options.zoomControl = typeof options.zoomControlOptions !== 'undefined';
        options.mapTypeControl = typeof options.mapTypeControlOptions !== 'undefined';
        options.streetViewControl = typeof options.streetViewControlOptions !== 'undefined';
        options.fullscreenControl = typeof options.fullscreenControlOptions !== 'undefined';

        return new library._Map(this.element, {
            ...options,
            center,
            zoom,
        });
    }

    protected doCreateMarker(
        definition: MarkerDefinition<google.maps.marker.AdvancedMarkerElementOptions, google.maps.InfoWindowOptions>
    ): google.maps.marker.AdvancedMarkerElement {
        const { position, title, infoWindow, extra, rawOptions = {}, ...otherOptions } = definition;

        const marker = new library.AdvancedMarkerElement({
            position,
            title,
            ...otherOptions,
            ...rawOptions,
            map: this.map,
        });

        if (infoWindow) {
            this.createInfoWindow({ definition: infoWindow, marker });
        }

        return marker;
    }

    protected doCreateInfoWindow({
        definition,
        marker,
    }: {
        definition: MarkerDefinition<
            google.maps.marker.AdvancedMarkerElementOptions,
            google.maps.InfoWindowOptions
        >['infoWindow'];
        marker: google.maps.marker.AdvancedMarkerElement;
    }): google.maps.InfoWindow {
        const { headerContent, content, extra, rawOptions = {}, ...otherOptions } = definition;

        const infoWindow = new library.InfoWindow({
            headerContent: this.createTextOrElement(headerContent),
            content: this.createTextOrElement(content),
            ...otherOptions,
            ...rawOptions,
        });

        if (definition.opened) {
            infoWindow.open({
                map: this.map,
                shouldFocus: false,
                anchor: marker,
            });
        }

        marker.addListener('click', () => {
            if (definition.autoClose) {
                this.closeInfoWindowsExcept(infoWindow);
            }

            infoWindow.open({
                map: this.map,
                anchor: marker,
            });
        });

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
