/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import type {
    CircleDefinition,
    Icon,
    InfoWindowDefinition,
    MapDefinition,
    MarkerDefinition,
    PolygonDefinition,
    PolylineDefinition,
    RectangleDefinition,
} from '@symfony/ux-map';
import AbstractMapController, { IconTypes } from '@symfony/ux-map';
import 'leaflet/dist/leaflet.min.css';
import type {
    CircleOptions,
    ControlPosition,
    LatLngBoundsExpression,
    MapOptions as LeafletMapOptions,
    MarkerOptions,
    PolylineOptions as PolygonOptions,
    PolylineOptions,
    PopupOptions,
    PolylineOptions as RectangleOptions,
} from 'leaflet';
import type * as L from 'leaflet';
import * as LeafletModule from 'leaflet';
import { createAdapter } from './leafletAdapter';

const adapter = createAdapter(LeafletModule);

// Symfony UX's default marker icon, used when the caller hasn't supplied one
// via `bridgeOptions.icon` or a custom `Icon` definition. Built once at
// module load rather than per-connect to avoid re-parsing the SVG for every
// controller instance on the page.
const defaultMarkerIcon = adapter.createDivIcon({
    html: '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" fill-rule="evenodd" stroke-linecap="round" clip-rule="evenodd" viewBox="0 0 500 820"><defs><linearGradient id="__sf_ux_map_gradient_marker_fill" x1="0" x2="1" y1="0" y2="0" gradientTransform="matrix(0 -37.57 37.57 0 416.45 541)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#126FC6"/><stop offset="1" stop-color="#4C9CD1"/></linearGradient><linearGradient id="__sf_ux_map_gradient_marker_border" x1="0" x2="1" y1="0" y2="0" gradientTransform="matrix(0 -19.05 19.05 0 414.48 522.49)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#2E6C97"/><stop offset="1" stop-color="#3883B7"/></linearGradient></defs><circle cx="252.31" cy="266.24" r="83.99" fill="#fff"/><path fill="url(#__sf_ux_map_gradient_marker_fill)" stroke="url(#__sf_ux_map_gradient_marker_border)" stroke-width="1.1" d="M416.54 503.61c-6.57 0-12.04 5.7-12.04 11.87 0 2.78 1.56 6.3 2.7 8.74l9.3 17.88 9.26-17.88c1.13-2.43 2.74-5.79 2.74-8.74 0-6.18-5.38-11.87-11.96-11.87Zm0 7.16a4.69 4.69 0 1 1-.02 9.4 4.69 4.69 0 0 1 .02-9.4Z" transform="translate(-7889.1 -9807.44) scale(19.54)"/></svg>',
    iconSize: [25, 41],
    iconAnchor: [12.5, 41],
    popupAnchor: [0, -41],
    className: '',
});

type MapOptions = Pick<LeafletMapOptions, 'attributionControl' | 'zoomControl'> & {
    attributionControlOptions?: { position: ControlPosition; prefix: string | false };
    zoomControlOptions?: {
        position: ControlPosition;
        zoomInText: string;
        zoomInTitle: string;
        zoomOutText: string;
        zoomOutTitle: string;
    };
    tileLayer: { url: string; attribution: string; options: Record<string, unknown> } | false;
};

export default class extends AbstractMapController<
    MapOptions,
    LeafletMapOptions,
    L.Map,
    MarkerOptions,
    L.Marker,
    PopupOptions,
    L.Popup,
    PolygonOptions,
    L.Polygon,
    PolylineOptions,
    L.Polyline,
    CircleOptions,
    L.Circle,
    RectangleOptions,
    L.Rectangle
> {
    declare map: L.Map;

    public centerValueChanged(): void {
        if (this.map && this.hasCenterValue && this.centerValue && this.hasZoomValue && this.zoomValue) {
            this.map.setView(this.centerValue, this.zoomValue);
        }
    }

    public zoomValueChanged(): void {
        if (this.map && this.hasZoomValue && this.zoomValue) {
            this.map.setZoom(this.zoomValue);
        }
    }

    public minZoomValueChanged(): void {
        if (this.map && this.hasMinZoomValue && this.minZoomValue) {
            this.map.setMinZoom(this.minZoomValue);
        }
    }

    public maxZoomValueChanged(): void {
        if (this.map && this.hasMaxZoomValue && this.maxZoomValue) {
            this.map.setMaxZoom(this.maxZoomValue);
        }
    }

    protected dispatchEvent(name: string, payload: Record<string, unknown> = {}): void {
        payload.L = adapter.legacyNamespace;
        this.dispatch(name, {
            prefix: 'ux:map',
            detail: payload,
        });
    }

    protected doCreateMap({ definition }: { definition: MapDefinition<MapOptions, LeafletMapOptions> }): L.Map {
        const { center, zoom, minZoom, maxZoom, options, bridgeOptions = {} } = definition;

        const map = adapter.createMap(this.element, {
            center: center === null ? undefined : center,
            zoom: zoom === null ? undefined : zoom,
            minZoom: minZoom === null ? undefined : minZoom,
            maxZoom: maxZoom === null ? undefined : maxZoom,
            attributionControl: false,
            zoomControl: false,
            ...options,
            ...bridgeOptions,
        });

        if (options.tileLayer) {
            adapter
                .createTileLayer(options.tileLayer.url, {
                    attribution: options.tileLayer.attribution,
                    ...options.tileLayer.options,
                })
                .addTo(map);
        }

        if (typeof options.attributionControlOptions !== 'undefined') {
            adapter.createAttributionControl({ ...options.attributionControlOptions }).addTo(map);
        }

        if (typeof options.zoomControlOptions !== 'undefined') {
            adapter.createZoomControl({ ...options.zoomControlOptions }).addTo(map);
        }

        return map;
    }

    protected doCreateMarker({ definition }: { definition: MarkerDefinition<MarkerOptions, PopupOptions> }): L.Marker {
        const { '@id': _id, position, title, infoWindow, icon, bridgeOptions = {} } = definition;

        const hasBridgeIcon = Object.prototype.hasOwnProperty.call(bridgeOptions, 'icon');
        const markerOptions: MarkerOptions = {
            title: title || undefined,
            riseOnHover: true,
            // Apply the Symfony UX default icon per-marker, unless the caller
            // already supplied one via bridgeOptions.icon or a custom icon
            // definition (handled via doCreateIcon below).
            ...(hasBridgeIcon || icon ? {} : { icon: defaultMarkerIcon }),
            ...bridgeOptions,
        };

        const marker = adapter.createMarker(position, markerOptions).addTo(this.map);

        if (infoWindow) {
            this.createInfoWindow({ definition: infoWindow, element: marker });
        }

        if (icon) {
            if (hasBridgeIcon) {
                console.warn(
                    '[Symfony UX Map] Defining "bridgeOptions.icon" for a marker with a custom icon is not supported and will be ignored.'
                );
            }

            this.doCreateIcon({ definition: icon, element: marker });
        }

        return marker;
    }

    protected doRemoveMarker(marker: L.Marker): void {
        marker.remove();
    }

    protected doCreatePolygon({
        definition,
    }: {
        definition: PolygonDefinition<PolygonOptions, PopupOptions>;
    }): L.Polygon {
        const { '@id': _id, points, infoWindow, bridgeOptions = {} } = definition;

        const polygon = adapter.createPolygon(points, { ...bridgeOptions }).addTo(this.map);

        if (infoWindow) {
            this.createInfoWindow({ definition: infoWindow, element: polygon });
        }

        return polygon;
    }

    protected doRemovePolygon(polygon: L.Polygon) {
        polygon.remove();
    }

    protected doCreatePolyline({
        definition,
    }: {
        definition: PolylineDefinition<PolylineOptions, PopupOptions>;
    }): L.Polyline {
        const { '@id': _id, points, infoWindow, bridgeOptions = {} } = definition;

        const polyline = adapter.createPolyline(points, { ...bridgeOptions }).addTo(this.map);

        if (infoWindow) {
            this.createInfoWindow({ definition: infoWindow, element: polyline });
        }

        return polyline;
    }

    protected doRemovePolyline(polyline: L.Polyline): void {
        polyline.remove();
    }

    protected doCreateCircle({ definition }: { definition: CircleDefinition<CircleOptions, PopupOptions> }): L.Circle {
        const { '@id': _id, center, radius, infoWindow, bridgeOptions = {} } = definition;

        const circle = adapter.createCircle(center, { radius, ...bridgeOptions }).addTo(this.map);

        if (infoWindow) {
            this.createInfoWindow({ definition: infoWindow, element: circle });
        }

        return circle;
    }

    protected doRemoveCircle(circle: L.Circle): void {
        circle.remove();
    }

    protected doCreateRectangle({
        definition,
    }: {
        definition: RectangleDefinition<RectangleOptions, PopupOptions>;
    }): L.Rectangle {
        const { '@id': _id, southWest, northEast, infoWindow, bridgeOptions = {} } = definition;

        const rectangle = adapter
            .createRectangle(
                [
                    [southWest.lat, southWest.lng],
                    [northEast.lat, northEast.lng],
                ],
                { ...bridgeOptions }
            )
            .addTo(this.map);

        if (infoWindow) {
            this.createInfoWindow({ definition: infoWindow, element: rectangle });
        }

        return rectangle;
    }

    protected doRemoveRectangle(rectangle: L.Rectangle): void {
        rectangle.remove();
    }

    protected doCreateInfoWindow({
        definition,
        element,
    }: {
        definition: Omit<InfoWindowDefinition<PopupOptions>, 'position'>;
        element: L.Marker | L.Polygon | L.Polyline | L.Circle | L.Rectangle;
    }): L.Popup {
        const { headerContent, content, opened, autoClose, bridgeOptions = {} } = definition;

        element.bindPopup([headerContent, content].filter((x) => x).join('<br>'), { ...bridgeOptions });

        if (opened) {
            if (autoClose) {
                this.closePopups();
            }

            setTimeout(() => element.openPopup(), 0);
        }

        const popup = element.getPopup();
        if (!popup) {
            throw new Error('Unable to get the Popup associated with the element.');
        }

        popup.on('click', () => {
            if (autoClose) {
                this.closePopups({ except: popup });
            }
        });

        return popup;
    }

    protected doCreateIcon({ definition, element }: { definition: Icon; element: L.Marker }): void {
        const { type, width, height } = definition;

        // `className: ''` suppresses Leaflet's default `.leaflet-div-icon`
        // / `.leaflet-marker-icon` styles, which would otherwise double-draw
        // a white background under the user-provided icon.
        const iconSize: [number, number] = [width, height];
        const className = '';

        let icon: L.DivIcon | L.Icon;
        if (type === IconTypes.Svg) {
            icon = adapter.createDivIcon({ html: definition.html, iconSize, className });
        } else if (type === IconTypes.UxIcon) {
            icon = adapter.createDivIcon({ html: definition._generated_html, iconSize, className });
        } else if (type === IconTypes.Url) {
            icon = adapter.createIcon({ iconUrl: definition.url, iconSize, className });
        } else {
            throw new Error(`Unsupported icon type: ${type}.`);
        }
        element.setIcon(icon);
    }

    protected doFitBoundsToMarkers(): void {
        if (this.markers.size === 0) {
            return;
        }

        const bounds: LatLngBoundsExpression = [];
        this.markers.forEach((marker) => {
            const position = marker.getLatLng();
            bounds.push([position.lat, position.lng]);
        });
        this.map.fitBounds(bounds);
    }

    private closePopups(options: { except?: L.Popup } = {}): void {
        this.infoWindows.forEach((popup) => {
            if (options.except && popup === options.except) {
                return;
            }
            popup.close();
        });
    }
}
