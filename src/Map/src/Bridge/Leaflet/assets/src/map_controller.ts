import AbstractMapController, { IconTypes } from '@symfony/ux-map';
import type {
    Icon,
    InfoWindowWithoutPositionDefinition,
    MarkerDefinition,
    Point,
    PolygonDefinition,
    PolylineDefinition,
} from '@symfony/ux-map';
import 'leaflet/dist/leaflet.min.css';
import * as L from 'leaflet';
import type {
    LatLngBoundsExpression,
    MapOptions as LeafletMapOptions,
    MarkerOptions,
    PolylineOptions as PolygonOptions,
    PolylineOptions,
    PopupOptions,
} from 'leaflet';

type MapOptions = Pick<LeafletMapOptions, 'center' | 'zoom'> & {
    tileLayer: { url: string; attribution: string; options: Record<string, unknown> };
};

export default class extends AbstractMapController<
    MapOptions,
    L.Map,
    MarkerOptions,
    L.Marker,
    PopupOptions,
    L.Popup,
    PolygonOptions,
    L.Polygon,
    PolylineOptions,
    L.Polyline
> {
    declare map: L.Map;

    connect(): void {
        L.Marker.prototype.options.icon = L.divIcon({
            html: '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" fill-rule="evenodd" stroke-linecap="round" clip-rule="evenodd" viewBox="0 0 500 820"><defs><linearGradient id="__sf_ux_map_gradient_marker_fill" x1="0" x2="1" y1="0" y2="0" gradientTransform="matrix(0 -37.57 37.57 0 416.45 541)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#126FC6"/><stop offset="1" stop-color="#4C9CD1"/></linearGradient><linearGradient id="__sf_ux_map_gradient_marker_border" x1="0" x2="1" y1="0" y2="0" gradientTransform="matrix(0 -19.05 19.05 0 414.48 522.49)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#2E6C97"/><stop offset="1" stop-color="#3883B7"/></linearGradient></defs><circle cx="252.31" cy="266.24" r="83.99" fill="#fff"/><path fill="url(#__sf_ux_map_gradient_marker_fill)" stroke="url(#__sf_ux_map_gradient_marker_border)" stroke-width="1.1" d="M416.54 503.61c-6.57 0-12.04 5.7-12.04 11.87 0 2.78 1.56 6.3 2.7 8.74l9.3 17.88 9.26-17.88c1.13-2.43 2.74-5.79 2.74-8.74 0-6.18-5.38-11.87-11.96-11.87Zm0 7.16a4.69 4.69 0 1 1-.02 9.4 4.69 4.69 0 0 1 .02-9.4Z" transform="translate(-7889.1 -9807.44) scale(19.54)"/></svg>',
            iconSize: [25, 41],
            iconAnchor: [12.5, 41],
            popupAnchor: [0, -41],
            className: '', // Adding an empty class to the icon to avoid the default Leaflet styles
        });

        super.connect();
    }

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

    protected dispatchEvent(name: string, payload: Record<string, unknown> = {}): void {
        this.dispatch(name, {
            prefix: 'ux:map',
            detail: {
                ...payload,
                L,
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

    protected doCreateMarker({ definition }: { definition: MarkerDefinition<MarkerOptions, PopupOptions> }): L.Marker {
        const { '@id': _id, position, title, infoWindow, icon, extra, rawOptions = {}, ...otherOptions } = definition;

        const marker = L.marker(position, { title: title || undefined, ...otherOptions, ...rawOptions }).addTo(
            this.map
        );

        if (infoWindow) {
            this.createInfoWindow({ definition: infoWindow, element: marker });
        }

        if (icon) {
            this.doCreateIcon({ definition: icon, element: marker });
        }

        return marker;
    }

    protected doRemoveMarker(marker: L.Marker): void {
        marker.remove();
    }

    protected doCreatePolygon({
        definition,
    }: { definition: PolygonDefinition<PolygonOptions, PopupOptions> }): L.Polygon {
        const { '@id': _id, points, title, infoWindow, rawOptions = {} } = definition;

        const polygon = L.polygon(points, { ...rawOptions }).addTo(this.map);

        if (title) {
            polygon.bindPopup(title);
        }

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
    }: { definition: PolylineDefinition<PolylineOptions, PopupOptions> }): L.Polyline {
        const { '@id': _id, points, title, infoWindow, rawOptions = {} } = definition;

        const polyline = L.polyline(points, { ...rawOptions }).addTo(this.map);

        if (title) {
            polyline.bindPopup(title);
        }

        if (infoWindow) {
            this.createInfoWindow({ definition: infoWindow, element: polyline });
        }

        return polyline;
    }

    protected doRemovePolyline(polyline: L.Polyline): void {
        polyline.remove();
    }

    protected doCreateInfoWindow({
        definition,
        element,
    }: {
        definition: InfoWindowWithoutPositionDefinition<PopupOptions>;
        element: L.Marker | L.Polygon | L.Polyline;
    }): L.Popup {
        const { headerContent, content, rawOptions = {}, ...otherOptions } = definition;

        element.bindPopup([headerContent, content].filter((x) => x).join('<br>'), { ...otherOptions, ...rawOptions });

        if (definition.opened) {
            element.openPopup();
        }

        const popup = element.getPopup();
        if (!popup) {
            throw new Error('Unable to get the Popup associated with the element.');
        }

        return popup;
    }

    protected doCreateIcon({
        definition,
        element,
    }: {
        definition: Icon;
        element: L.Marker;
    }): void {
        const { type, width, height } = definition;

        let icon: L.DivIcon | L.Icon;
        if (type === IconTypes.Svg) {
            icon = L.divIcon({
                html: definition.html,
                iconSize: [width, height],
                className: '', // Adding an empty class to the icon to avoid the default Leaflet styles
            });
        } else if (type === IconTypes.UxIcon) {
            icon = L.divIcon({
                html: definition._generated_html,
                iconSize: [width, height],
                className: '', // Adding an empty class to the icon to avoid the default Leaflet styles
            });
        } else if (type === IconTypes.Url) {
            icon = L.icon({
                iconUrl: definition.url,
                iconSize: [width, height],
                className: '', // Adding an empty class to the icon to avoid the default Leaflet styles
            });
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
}
