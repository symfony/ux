import AbstractMapController from '@symfony/ux-map/abstract-map-controller';
import { Loader } from '@googlemaps/js-api-loader';

let loader;
let library;
class default_1 extends AbstractMapController {
    async connect() {
        if (!loader) {
            loader = new Loader(this.providerOptionsValue);
        }
        const { Map: _Map, InfoWindow } = await loader.importLibrary('maps');
        const { AdvancedMarkerElement } = await loader.importLibrary('marker');
        library = { _Map, AdvancedMarkerElement, InfoWindow };
        super.connect();
    }
    doCreateMap({ center, zoom, options, }) {
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
    doCreateMarker(definition) {
        const { position, title, infoWindow, rawOptions = {}, ...otherOptions } = definition;
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
    doCreateInfoWindow({ definition, marker, }) {
        const { headerContent, content, rawOptions = {}, ...otherOptions } = definition;
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
