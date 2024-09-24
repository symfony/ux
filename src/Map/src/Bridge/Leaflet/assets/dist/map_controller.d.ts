import AbstractMapController from '@symfony/ux-map';
import type { Point, MarkerDefinition, PolygonDefinition } from '@symfony/ux-map';
import 'leaflet/dist/leaflet.min.css';
import * as L from 'leaflet';
import type { MapOptions as LeafletMapOptions, MarkerOptions, PopupOptions, PolygonOptions } from 'leaflet';
type MapOptions = Pick<LeafletMapOptions, 'center' | 'zoom'> & {
    tileLayer: {
        url: string;
        attribution: string;
        options: Record<string, unknown>;
    };
};
export default class extends AbstractMapController<MapOptions, typeof L.Map, MarkerOptions, typeof L.Marker, PopupOptions, typeof L.Popup, PolygonOptions, typeof L.Polygon> {
    connect(): void;
    protected dispatchEvent(name: string, payload?: Record<string, unknown>): void;
    protected doCreateMap({ center, zoom, options, }: {
        center: Point | null;
        zoom: number | null;
        options: MapOptions;
    }): L.Map;
    protected doCreateMarker(definition: MarkerDefinition): L.Marker;
    protected doCreatePolygon(definition: PolygonDefinition): L.Polygon;
    protected doCreateInfoWindow({ definition, element, }: {
        definition: MarkerDefinition['infoWindow'] | PolygonDefinition['infoWindow'];
        element: L.Marker | L.Polygon;
    }): L.Popup;
    protected doFitBoundsToMarkers(): void;
}
export {};
