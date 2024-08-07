import AbstractMapController from '@symfony/ux-map/abstract-map-controller';
import type { Point, MarkerDefinition } from '@symfony/ux-map/abstract-map-controller';
import 'leaflet/dist/leaflet.min.css';
import { type Map as LeafletMap, Marker, type Popup } from 'leaflet';
import type { MapOptions as LeafletMapOptions, MarkerOptions, PopupOptions } from 'leaflet';
type MapOptions = Pick<LeafletMapOptions, 'center' | 'zoom'> & {
    tileLayer: {
        url: string;
        attribution: string;
        options: Record<string, unknown>;
    };
};
export default class extends AbstractMapController<MapOptions, typeof LeafletMap, MarkerOptions, Marker, Popup, PopupOptions> {
    connect(): void;
    protected doCreateMap({ center, zoom, options }: {
        center: Point;
        zoom: number;
        options: MapOptions;
    }): LeafletMap;
    protected doCreateMarker(definition: MarkerDefinition): Marker;
    protected doCreateInfoWindow({ definition, marker, }: {
        definition: MarkerDefinition['infoWindow'];
        marker: Marker;
    }): Popup;
    protected doFitBoundsToMarkers(): void;
}
export {};
