import AbstractMapController from '@symfony/ux-map';
import type { Icon, InfoWindowWithoutPositionDefinition, MarkerDefinition, Point, PolygonDefinition, PolylineDefinition } from '@symfony/ux-map';
import 'leaflet/dist/leaflet.min.css';
import * as L from 'leaflet';
import type { MapOptions as LeafletMapOptions, MarkerOptions, PolylineOptions as PolygonOptions, PolylineOptions, PopupOptions } from 'leaflet';
type MapOptions = Pick<LeafletMapOptions, 'center' | 'zoom'> & {
    tileLayer: {
        url: string;
        attribution: string;
        options: Record<string, unknown>;
    };
};
export default class extends AbstractMapController<MapOptions, L.Map, MarkerOptions, L.Marker, PopupOptions, L.Popup, PolygonOptions, L.Polygon, PolylineOptions, L.Polyline> {
    map: L.Map;
    connect(): void;
    centerValueChanged(): void;
    zoomValueChanged(): void;
    protected dispatchEvent(name: string, payload?: Record<string, unknown>): void;
    protected doCreateMap({ center, zoom, options, }: {
        center: Point | null;
        zoom: number | null;
        options: MapOptions;
    }): L.Map;
    protected doCreateMarker({ definition }: {
        definition: MarkerDefinition<MarkerOptions, PopupOptions>;
    }): L.Marker;
    protected doRemoveMarker(marker: L.Marker): void;
    protected doCreatePolygon({ definition, }: {
        definition: PolygonDefinition<PolygonOptions, PopupOptions>;
    }): L.Polygon;
    protected doRemovePolygon(polygon: L.Polygon): void;
    protected doCreatePolyline({ definition, }: {
        definition: PolylineDefinition<PolylineOptions, PopupOptions>;
    }): L.Polyline;
    protected doRemovePolyline(polyline: L.Polyline): void;
    protected doCreateInfoWindow({ definition, element, }: {
        definition: InfoWindowWithoutPositionDefinition<PopupOptions>;
        element: L.Marker | L.Polygon | L.Polyline;
    }): L.Popup;
    protected doCreateIcon({ definition, element, }: {
        definition: Icon;
        element: L.Marker;
    }): void;
    protected doFitBoundsToMarkers(): void;
}
export {};
