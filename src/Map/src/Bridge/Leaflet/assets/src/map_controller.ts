import AbstractMapController from '@symfony/ux-map/abstract-map-controller';
import type { Point, MarkerDefinition } from '@symfony/ux-map/abstract-map-controller';
import 'leaflet/dist/leaflet.min.css';
import * as L from 'leaflet';
import type { MapOptions as LeafletMapOptions, MarkerOptions, PopupOptions } from 'leaflet';

type MapOptions = Pick<LeafletMapOptions, 'center' | 'zoom'> & {
    tileLayer: { url: string; attribution: string; options: Record<string, unknown> };
};

export default class extends AbstractMapController<
    MapOptions,
    typeof L.Map,
    MarkerOptions,
    typeof L.Marker,
    typeof L.Popup,
    PopupOptions
> {
    connect(): void {
        L.Marker.prototype.options.icon = L.divIcon({
            html: '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" fill-rule="evenodd" stroke-linecap="round" clip-rule="evenodd" viewBox="0 0 500 820"><defs><linearGradient id="__sf_ux_map_gradient_marker_fill" x1="0" x2="1" y1="0" y2="0" gradientTransform="matrix(0 -37.57 37.57 0 416.45 541)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#126FC6"/><stop offset="1" stop-color="#4C9CD1"/></linearGradient><linearGradient id="__sf_ux_map_gradient_marker_border" x1="0" x2="1" y1="0" y2="0" gradientTransform="matrix(0 -19.05 19.05 0 414.48 522.49)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#2E6C97"/><stop offset="1" stop-color="#3883B7"/></linearGradient></defs><circle cx="252.31" cy="266.24" r="83.99" fill="#fff"/><path fill="url(#__sf_ux_map_gradient_marker_fill)" stroke="url(#__sf_ux_map_gradient_marker_border)" stroke-width="1.1" d="M416.54 503.61c-6.57 0-12.04 5.7-12.04 11.87 0 2.78 1.56 6.3 2.7 8.74l9.3 17.88 9.26-17.88c1.13-2.43 2.74-5.79 2.74-8.74 0-6.18-5.38-11.87-11.96-11.87Zm0 7.16a4.69 4.69 0 1 1-.02 9.4 4.69 4.69 0 0 1 .02-9.4Z" transform="translate(-7889.1 -9807.44) scale(19.54)"/></svg>',
            iconSize: [25, 41],
            iconAnchor: [12.5, 41],
            popupAnchor: [0, -41],
            className: '',
        });

        super.connect();
    }

    protected dispatchEvent(name: string, payload: Record<string, unknown> = {}): void {
        this.dispatch(name, {
            prefix: 'ux:map',
            detail: {
                ...payload,
                leaflet: L,
            },
        });
    }

    protected doCreateMap({
        center,
        zoom,
        options,
    }: { center: Point | null; zoom: number | null; options: MapOptions }): L.Map {
        const map = L.map(this.element, {
            ...options,
            center: center === null ? undefined : center,
            zoom: zoom === null ? undefined : zoom,
        });

        L.tileLayer(options.tileLayer.url, {
            attribution: options.tileLayer.attribution,
            ...options.tileLayer.options,
        }).addTo(map);

        return map;
    }

    protected doCreateMarker(definition: MarkerDefinition): L.Marker {
        const { position, title, infoWindow, extra, rawOptions = {}, ...otherOptions } = definition;

        const marker = L.marker(position, { title, ...otherOptions, ...rawOptions }).addTo(this.map);

        if (infoWindow) {
            if (infoWindow.opened) {
                this.createInfoWindow({ definition: infoWindow, marker });
            } else {
                marker.on('click', () => {
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
        definition: MarkerDefinition['infoWindow'];
        marker: L.Marker;
        onMarkerClick: boolean;
    }): L.Popup {
        const { headerContent, content, extra, rawOptions = {}, ...otherOptions } = definition;

        marker.bindPopup([headerContent, content].filter((x) => x).join('<br>'), { ...otherOptions, ...rawOptions });
        if (definition.opened || onMarkerClick) {
            marker.openPopup();
        }

        const popup = marker.getPopup();
        if (!popup) {
            throw new Error('Unable to get the Popup associated to the Marker, this should not happens.');
        }
        return popup;
    }

    protected doFitBoundsToMarkers(): void {
        if (this.markers.length === 0) {
            return;
        }

        this.map.fitBounds(
            this.markers.map((marker: L.Marker) => {
                const position = marker.getLatLng();

                return [position.lat, position.lng];
            })
        );
    }
}
