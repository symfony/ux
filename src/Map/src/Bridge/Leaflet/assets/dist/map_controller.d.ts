import AbstractMapController from '@symfony/ux-map/abstract-map-controller';
import type { Point, MarkerDefinition } from '@symfony/ux-map/abstract-map-controller';
import 'leaflet/dist/leaflet.min.css';
import * as L from 'leaflet';
import type { MapOptions as LeafletMapOptions, MarkerOptions, PopupOptions } from 'leaflet';
type MapOptions = Pick<LeafletMapOptions, 'center' | 'zoom'> & {
    tileLayer: {
        url: string;
        attribution: string;
        options: Record<string, unknown>;
    };
};
export default class extends AbstractMapController<MapOptions, typeof L.Map, MarkerOptions, typeof L.Marker, typeof L.Popup, PopupOptions> {
    connect(): void;
    protected dispatchEvent(name: string, payload?: Record<string, unknown>): void;
    protected doCreateMap({ center, zoom, options, }: {
        center: Point | null;
        zoom: number | null;
        options: MapOptions;
    }): L.Map;
    protected doCreateMarker(definition: MarkerDefinition): L.Marker;
    protected doCreateInfoWindow({ definition, marker, }: {
        definition: MarkerDefinition['infoWindow'];
        marker: L.Marker;
    }): L.Popup;
    protected doFitBoundsToMarkers(): void;
}
export {};
