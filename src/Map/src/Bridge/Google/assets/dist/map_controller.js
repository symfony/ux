import { Loader } from '@googlemaps/js-api-loader';
import { Controller } from '@hotwired/stimulus';

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

let _google;
const parser = new DOMParser();
class map_controller extends default_1 {
    async connect() {
        if (!_google) {
            _google = { maps: {} };
            let { libraries = [], ...loaderOptions } = this.providerOptionsValue;
            const loader = new Loader(loaderOptions);
            libraries = ['core', ...libraries.filter((library) => library !== 'core')];
            const librariesImplementations = await Promise.all(libraries.map((library) => loader.importLibrary(library)));
            librariesImplementations.map((libraryImplementation, index) => {
                if (typeof libraryImplementation !== 'object' || libraryImplementation === null) {
                    return;
                }
                const library = libraries[index];
                if (['marker', 'places', 'geometry', 'journeySharing', 'drawing', 'visualization'].includes(library)) {
                    _google.maps[library] = libraryImplementation;
                }
                else {
                    _google.maps = { ..._google.maps, ...libraryImplementation };
                }
            });
        }
        super.connect();
        this.parser = new DOMParser();
    }
    centerValueChanged() {
        if (this.map && this.hasCenterValue && this.centerValue) {
            this.map.setCenter(this.centerValue);
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
                google: _google,
            },
        });
    }
    doCreateMap({ center, zoom, options, }) {
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
    doCreateMarker({ definition, }) {
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
    doRemoveMarker(marker) {
        marker.map = null;
    }
    doCreatePolygon({ definition, }) {
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
    doRemovePolygon(polygon) {
        polygon.setMap(null);
    }
    doCreatePolyline({ definition, }) {
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
    doRemovePolyline(polyline) {
        polyline.setMap(null);
    }
    doCreateInfoWindow({ definition, element, }) {
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
        }
        else if (element instanceof google.maps.Polygon) {
            element.addListener('click', (event) => {
                if (definition.autoClose) {
                    this.closeInfoWindowsExcept(infoWindow);
                }
                infoWindow.setPosition(event.latLng);
                infoWindow.open(this.map);
            });
            if (definition.opened) {
                const bounds = new google.maps.LatLngBounds();
                element.getPath().forEach((point) => {
                    bounds.extend(point);
                });
                infoWindow.setPosition(bounds.getCenter());
                infoWindow.open({ map: this.map, anchor: element });
            }
        }
        return infoWindow;
    }
    doFitBoundsToMarkers() {
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
    createTextOrElement(content) {
        if (!content) {
            return null;
        }
        if (content.includes('<')) {
            const div = document.createElement('div');
            div.innerHTML = content;
            return div;
        }
        return content;
    }
    doCreateIcon({ definition, element, }) {
        const { type, width, height } = definition;
        if (type === IconTypes.Svg) {
            element.content = parser.parseFromString(definition.html, 'image/svg+xml').documentElement;
        }
        else if (type === IconTypes.UxIcon) {
            element.content = parser.parseFromString(definition._generated_html, 'image/svg+xml').documentElement;
        }
        else if (type === IconTypes.Url) {
            const icon = document.createElement('img');
            icon.width = width;
            icon.height = height;
            icon.src = definition.url;
            element.content = icon;
        }
        else {
            throw new Error(`Unsupported icon type: ${type}.`);
        }
    }
    closeInfoWindowsExcept(infoWindow) {
        this.infoWindows.forEach((otherInfoWindow) => {
            if (otherInfoWindow !== infoWindow) {
                otherInfoWindow.close();
            }
        });
    }
}

export { map_controller as default };
