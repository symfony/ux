import { Controller } from '@hotwired/stimulus';

class default_1 extends Controller {
    constructor() {
        super(...arguments);
        this.markers = [];
        this.infoWindows = [];
    }
    connect() {
        const { center, zoom, options, markers, fitBoundsToMarkers } = this.viewValue;
        this.dispatchEvent('pre-connect', { options });
        this.map = this.doCreateMap({ center, zoom, options });
        markers.forEach((marker) => this.createMarker(marker));
        if (fitBoundsToMarkers) {
            this.doFitBoundsToMarkers();
        }
        this.dispatchEvent('connect', {
            map: this.map,
            markers: this.markers,
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
    createInfoWindow({ definition, marker, onMarkerClick = false, }) {
        this.dispatchEvent('info-window:before-create', { definition, marker });
        const infoWindow = this.doCreateInfoWindow({ definition, marker, onMarkerClick });
        this.dispatchEvent('info-window:after-create', { infoWindow, marker });
        this.infoWindows.push(infoWindow);
        return infoWindow;
    }
}
default_1.values = {
    providerOptions: Object,
    view: Object,
};

export { default_1 as default };
