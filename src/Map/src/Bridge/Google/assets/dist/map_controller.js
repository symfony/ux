import { Controller } from '@hotwired/stimulus';
import { Loader } from '@googlemaps/js-api-loader';

let default_1$1 = class default_1 extends Controller {
    constructor() {
        super(...arguments);
        this.markers = [];
        this.infoWindows = [];
        this.polygons = [];
    }
    connect() {
        const { center, zoom, options, markers, polygons, fitBoundsToMarkers } = this.viewValue;
        this.dispatchEvent('pre-connect', { options });
        this.map = this.doCreateMap({ center, zoom, options });
        markers.forEach((marker) => this.createMarker(marker));
        polygons.forEach((polygon) => this.createPolygon(polygon));
        if (fitBoundsToMarkers) {
            this.doFitBoundsToMarkers();
        }
        this.dispatchEvent('connect', {
            map: this.map,
            markers: this.markers,
            polygons: this.polygons,
            infoWindows: this.infoWindows,
        });
    }
    createMarker(definition) {
        this.dispatchEvent('marker:before-create', { definition });
        const marker = this.doCreateMarker(definition);
        this.dispatchEvent('marker:after-create', { marker });
        this.markers.push(marker);
        return marker;
    }
    createPolygon(definition) {
        this.dispatchEvent('polygon:before-create', { definition });
        const polygon = this.doCreatePolygon(definition);
        this.dispatchEvent('polygon:after-create', { polygon });
        this.polygons.push(polygon);
        return polygon;
    }
    createInfoWindow({ definition, element, }) {
        this.dispatchEvent('info-window:before-create', { definition, element });
        const infoWindow = this.doCreateInfoWindow({ definition, element });
        this.dispatchEvent('info-window:after-create', { infoWindow, element });
        this.infoWindows.push(infoWindow);
        return infoWindow;
    }
};
default_1$1.values = {
    providerOptions: Object,
    view: Object,
};

let _google;
class default_1 extends default_1$1 {
    async connect() {
        if (!_google) {
            _google = { maps: {} };
            let { libraries = [], ...loaderOptions } = this.providerOptionsValue;
            const loader = new Loader(loaderOptions);
            libraries = ['core', ...libraries.filter((library) => library !== 'core')];
            const librariesImplementations = await Promise.all(libraries.map((library) => loader.importLibrary(library)));
            librariesImplementations.map((libraryImplementation, index) => {
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
    doCreateMarker(definition) {
        const { position, title, infoWindow, extra, rawOptions = {}, ...otherOptions } = definition;
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
        return marker;
    }
    doCreatePolygon(definition) {
        const { points, title, infoWindow, rawOptions = {} } = definition;
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
    doCreateInfoWindow({ definition, element, }) {
        // :)
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
    closeInfoWindowsExcept(infoWindow) {
        this.infoWindows.forEach((otherInfoWindow) => {
            if (otherInfoWindow !== infoWindow) {
                otherInfoWindow.close();
            }
        });
    }
    doFitBoundsToMarkers() {
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
default_1.values = {
    providerOptions: Object,
};

export { default_1 as default };
