import AbstractMapController from '@symfony/ux-map/abstract-map-controller';
import 'leaflet/dist/leaflet.min.css';
import { Marker, divIcon, map, tileLayer, marker } from 'leaflet';

class map_controller extends AbstractMapController {
    connect() {
        Marker.prototype.options.icon = divIcon({
            html: '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linecap:round" viewBox="0 0 500 820"><defs><linearGradient id="a" x1="0" x2="1" y1="0" y2="0" gradientTransform="rotate(-90 478.727 62.272) scale(37.566)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#126FC6"/><stop offset="1" stop-color="#4C9CD1"/></linearGradient><linearGradient id="b" x1="0" x2="1" y1="0" y2="0" gradientTransform="rotate(-90 468.484 54.002) scale(19.053)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#2E6C97"/><stop offset="1" stop-color="#3883B7"/></linearGradient></defs><path fill="url(#a)" stroke="url(#b)" stroke-width="1.1" d="M416.544 503.612c-6.573 0-12.044 5.691-12.044 11.866 0 2.778 1.564 6.308 2.694 8.746l9.306 17.872 9.262-17.872c1.13-2.438 2.738-5.791 2.738-8.746 0-6.175-5.383-11.866-11.956-11.866Zm0 7.155a4.714 4.714 0 0 1 4.679 4.71c0 2.588-2.095 4.663-4.679 4.679-2.584-.017-4.679-2.09-4.679-4.679a4.714 4.714 0 0 1 4.679-4.71Z" transform="translate(-7889.1 -9807.44) scale(19.5417)"/></svg>',
            iconSize: [25, 41],
            iconAnchor: [12.5, 41],
            popupAnchor: [0, -41],
            className: '',
        });
        super.connect();
    }
    doCreateMap({ center, zoom, options }) {
        const map$1 = map(this.element, {
            ...options,
            center,
            zoom,
        });
        tileLayer(options.tileLayer.url, {
            attribution: options.tileLayer.attribution,
            ...options.tileLayer.options,
        }).addTo(map$1);
        return map$1;
    }
    doCreateMarker(definition) {
        const { position, title, infoWindow, rawOptions = {}, ...otherOptions } = definition;
        const marker$1 = marker(position, { title, ...otherOptions, ...rawOptions }).addTo(this.map);
        if (infoWindow) {
            this.createInfoWindow({ definition: infoWindow, marker: marker$1 });
        }
        return marker$1;
    }
    doCreateInfoWindow({ definition, marker, }) {
        const { headerContent, content, rawOptions = {}, ...otherOptions } = definition;
        marker.bindPopup([headerContent, content].filter((x) => x).join('<br>'), { ...otherOptions, ...rawOptions });
        if (definition.opened) {
            marker.openPopup();
        }
        const popup = marker.getPopup();
        if (!popup) {
            throw new Error('Unable to get the Popup associated to the Marker, this should not happens.');
        }
        return popup;
    }
    doFitBoundsToMarkers() {
        if (this.markers.length === 0) {
            return;
        }
        this.map.fitBounds(this.markers.map((marker) => {
            const position = marker.getLatLng();
            return [position.lat, position.lng];
        }));
    }
}

export { map_controller as default };
