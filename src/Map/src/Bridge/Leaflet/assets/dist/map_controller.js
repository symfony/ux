import AbstractMapController from '@symfony/ux-map/abstract-map-controller';
import 'leaflet/dist/leaflet.min.css';
import { Marker, divIcon, map, tileLayer, marker } from 'leaflet';

class map_controller extends AbstractMapController {
    connect() {
        Marker.prototype.options.icon = divIcon({
            html: '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" fill-rule="evenodd" stroke-linecap="round" clip-rule="evenodd" viewBox="0 0 500 820"><defs><linearGradient id="a" x1="0" x2="1" y1="0" y2="0" gradientTransform="matrix(0 -37.57 37.57 0 416.45 541)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#126FC6"/><stop offset="1" stop-color="#4C9CD1"/></linearGradient><linearGradient id="b" x1="0" x2="1" y1="0" y2="0" gradientTransform="matrix(0 -19.05 19.05 0 414.48 522.49)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#2E6C97"/><stop offset="1" stop-color="#3883B7"/></linearGradient></defs><circle cx="252.31" cy="266.24" r="83.99" fill="#fff"/><path fill="url(#a)" stroke="url(#b)" stroke-width="1.1" d="M416.54 503.61c-6.57 0-12.04 5.7-12.04 11.87 0 2.78 1.56 6.3 2.7 8.74l9.3 17.88 9.26-17.88c1.13-2.43 2.74-5.79 2.74-8.74 0-6.18-5.38-11.87-11.96-11.87Zm0 7.16a4.69 4.69 0 1 1-.02 9.4 4.69 4.69 0 0 1 .02-9.4Z" transform="translate(-7889.1 -9807.44) scale(19.54)"/></svg>',
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
