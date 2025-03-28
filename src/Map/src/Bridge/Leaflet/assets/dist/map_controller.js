import { Controller } from '@hotwired/stimulus';
import 'leaflet/dist/leaflet.min.css';
import * as L from 'leaflet';

const IconTypes = {
    Url: 'url',
    Svg: 'svg',
    UxIcon: 'ux-icon',
};
class default_1 extends Controller {
    constructor() {
        super(...arguments);
        this.markers = new Map();
        this.polygons = new Map();
        this.polylines = new Map();
        this.infoWindows = [];
        this.isConnected = false;
    }
    connect() {
        const options = this.optionsValue;
        this.dispatchEvent('pre-connect', { options });
        this.createMarker = this.createDrawingFactory('marker', this.markers, this.doCreateMarker.bind(this));
        this.createPolygon = this.createDrawingFactory('polygon', this.polygons, this.doCreatePolygon.bind(this));
        this.createPolyline = this.createDrawingFactory('polyline', this.polylines, this.doCreatePolyline.bind(this));
        this.map = this.doCreateMap({
            center: this.hasCenterValue ? this.centerValue : null,
            zoom: this.hasZoomValue ? this.zoomValue : null,
            options,
        });
        this.markersValue.forEach((definition) => this.createMarker({ definition }));
        this.polygonsValue.forEach((definition) => this.createPolygon({ definition }));
        this.polylinesValue.forEach((definition) => this.createPolyline({ definition }));
        if (this.fitBoundsToMarkersValue) {
            this.doFitBoundsToMarkers();
        }
        this.dispatchEvent('connect', {
            map: this.map,
            markers: [...this.markers.values()],
            polygons: [...this.polygons.values()],
            polylines: [...this.polylines.values()],
            infoWindows: this.infoWindows,
        });
        this.isConnected = true;
    }
    createInfoWindow({ definition, element, }) {
        this.dispatchEvent('info-window:before-create', { definition, element });
        const infoWindow = this.doCreateInfoWindow({ definition, element });
        this.dispatchEvent('info-window:after-create', { infoWindow, element });
        this.infoWindows.push(infoWindow);
        return infoWindow;
    }
    markersValueChanged() {
        if (!this.isConnected) {
            return;
        }
        this.onDrawChanged(this.markers, this.markersValue, this.createMarker, this.doRemoveMarker);
        if (this.fitBoundsToMarkersValue) {
            this.doFitBoundsToMarkers();
        }
    }
    polygonsValueChanged() {
        if (!this.isConnected) {
            return;
        }
        this.onDrawChanged(this.polygons, this.polygonsValue, this.createPolygon, this.doRemovePolygon);
    }
    polylinesValueChanged() {
        if (!this.isConnected) {
            return;
        }
        this.onDrawChanged(this.polylines, this.polylinesValue, this.createPolyline, this.doRemovePolyline);
    }
    createDrawingFactory(type, draws, factory) {
        const eventBefore = `${type}:before-create`;
        const eventAfter = `${type}:after-create`;
        return ({ definition }) => {
            this.dispatchEvent(eventBefore, { definition });
            const drawing = factory({ definition });
            this.dispatchEvent(eventAfter, { [type]: drawing });
            draws.set(definition['@id'], drawing);
            return drawing;
        };
    }
    onDrawChanged(draws, newDrawDefinitions, factory, remover) {
        const idsToRemove = new Set(draws.keys());
        newDrawDefinitions.forEach((definition) => {
            idsToRemove.delete(definition['@id']);
        });
        idsToRemove.forEach((id) => {
            const draw = draws.get(id);
            remover(draw);
            draws.delete(id);
        });
        newDrawDefinitions.forEach((definition) => {
            if (!draws.has(definition['@id'])) {
                factory({ definition });
            }
        });
    }
}
default_1.values = {
    providerOptions: Object,
    center: Object,
    zoom: Number,
    fitBoundsToMarkers: Boolean,
    markers: Array,
    polygons: Array,
    polylines: Array,
    options: Object,
};

class map_controller extends default_1 {
    connect() {
        L.Marker.prototype.options.icon = L.divIcon({
            html: '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" fill-rule="evenodd" stroke-linecap="round" clip-rule="evenodd" viewBox="0 0 500 820"><defs><linearGradient id="__sf_ux_map_gradient_marker_fill" x1="0" x2="1" y1="0" y2="0" gradientTransform="matrix(0 -37.57 37.57 0 416.45 541)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#126FC6"/><stop offset="1" stop-color="#4C9CD1"/></linearGradient><linearGradient id="__sf_ux_map_gradient_marker_border" x1="0" x2="1" y1="0" y2="0" gradientTransform="matrix(0 -19.05 19.05 0 414.48 522.49)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#2E6C97"/><stop offset="1" stop-color="#3883B7"/></linearGradient></defs><circle cx="252.31" cy="266.24" r="83.99" fill="#fff"/><path fill="url(#__sf_ux_map_gradient_marker_fill)" stroke="url(#__sf_ux_map_gradient_marker_border)" stroke-width="1.1" d="M416.54 503.61c-6.57 0-12.04 5.7-12.04 11.87 0 2.78 1.56 6.3 2.7 8.74l9.3 17.88 9.26-17.88c1.13-2.43 2.74-5.79 2.74-8.74 0-6.18-5.38-11.87-11.96-11.87Zm0 7.16a4.69 4.69 0 1 1-.02 9.4 4.69 4.69 0 0 1 .02-9.4Z" transform="translate(-7889.1 -9807.44) scale(19.54)"/></svg>',
            iconSize: [25, 41],
            iconAnchor: [12.5, 41],
            popupAnchor: [0, -41],
            className: '',
        });
        super.connect();
    }
    centerValueChanged() {
        if (this.map && this.hasCenterValue && this.centerValue && this.hasZoomValue && this.zoomValue) {
            this.map.setView(this.centerValue, this.zoomValue);
        }
    }
    zoomValueChanged() {
        if (this.map && this.hasZoomValue && this.zoomValue) {
            this.map.setZoom(this.zoomValue);
        }
    }
    dispatchEvent(name, payload = {}) {
        this.dispatch(name, {
            prefix: 'ux:map',
            detail: {
                ...payload,
                L,
            },
        });
    }
    doCreateMap({ center, zoom, options, }) {
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
    doCreateMarker({ definition }) {
        const { '@id': _id, position, title, infoWindow, icon, extra, rawOptions = {}, ...otherOptions } = definition;
        const marker = L.marker(position, { title: title || undefined, ...otherOptions, ...rawOptions }).addTo(this.map);
        if (infoWindow) {
            this.createInfoWindow({ definition: infoWindow, element: marker });
        }
        if (icon) {
            this.doCreateIcon({ definition: icon, element: marker });
        }
        return marker;
    }
    doRemoveMarker(marker) {
        marker.remove();
    }
    doCreatePolygon({ definition, }) {
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
    doRemovePolygon(polygon) {
        polygon.remove();
    }
    doCreatePolyline({ definition, }) {
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
    doRemovePolyline(polyline) {
        polyline.remove();
    }
    doCreateInfoWindow({ definition, element, }) {
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
    doCreateIcon({ definition, element, }) {
        const { type, width, height } = definition;
        let icon;
        if (type === IconTypes.Svg) {
            icon = L.divIcon({
                html: definition.html,
                iconSize: [width, height],
                className: '',
            });
        }
        else if (type === IconTypes.UxIcon) {
            icon = L.divIcon({
                html: definition._generated_html,
                iconSize: [width, height],
                className: '',
            });
        }
        else if (type === IconTypes.Url) {
            icon = L.icon({
                iconUrl: definition.url,
                iconSize: [width, height],
                className: '',
            });
        }
        else {
            throw new Error(`Unsupported icon type: ${type}.`);
        }
        element.setIcon(icon);
    }
    doFitBoundsToMarkers() {
        if (this.markers.size === 0) {
            return;
        }
        const bounds = [];
        this.markers.forEach((marker) => {
            const position = marker.getLatLng();
            bounds.push([position.lat, position.lng]);
        });
        this.map.fitBounds(bounds);
    }
}

export { map_controller as default };
