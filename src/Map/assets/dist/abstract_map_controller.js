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

export { IconTypes, default_1 as default };
