import type { CircleDefinition, Icon, InfoWindowDefinition, MapDefinition, MarkerDefinition, PolygonDefinition, PolylineDefinition, RectangleDefinition } from '@symfony/ux-map';
import AbstractMapController from '@symfony/ux-map';
import 'leaflet/dist/leaflet.min.css';
import type { CircleOptions, ControlPosition, MapOptions as LeafletMapOptions, MarkerOptions, PolylineOptions as PolygonOptions, PolylineOptions, PopupOptions, PolylineOptions as RectangleOptions } from 'leaflet';
import * as L from 'leaflet';
type MapOptions = Pick<LeafletMapOptions, 'attributionControl' | 'zoomControl'> & {
    attributionControlOptions?: {
        position: ControlPosition;
        prefix: string | false;
    };
    zoomControlOptions?: {
        position: ControlPosition;
        zoomInText: string;
        zoomInTitle: string;
        zoomOutText: string;
        zoomOutTitle: string;
    };
    tileLayer: {
        url: string;
        attribution: string;
        options: Record<string, unknown>;
    } | false;
};
export default class extends AbstractMapController<MapOptions, LeafletMapOptions, L.Map, MarkerOptions, L.Marker, PopupOptions, L.Popup, PolygonOptions, L.Polygon, PolylineOptions, L.Polyline, CircleOptions, L.Circle, RectangleOptions, L.Rectangle> {
    map: L.Map;
    connect(): void;
    centerValueChanged(): void;
    zoomValueChanged(): void;
    protected dispatchEvent(name: string, payload?: Record<string, unknown>): void;
    protected doCreateMap({ definition }: {
        definition: MapDefinition<MapOptions, LeafletMapOptions>;
    }): L.Map;
    protected doCreateMarker({ definition }: {
        definition: MarkerDefinition<MarkerOptions, PopupOptions>;
    }): L.Marker;
    protected doRemoveMarker(marker: L.Marker): void;
    protected doCreatePolygon({ definition }: {
        definition: PolygonDefinition<PolygonOptions, PopupOptions>;
    }): L.Polygon;
    protected doRemovePolygon(polygon: L.Polygon): void;
    protected doCreatePolyline({ definition }: {
        definition: PolylineDefinition<PolylineOptions, PopupOptions>;
    }): L.Polyline;
    protected doRemovePolyline(polyline: L.Polyline): void;
    protected doCreateCircle({ definition }: {
        definition: CircleDefinition<CircleOptions, PopupOptions>;
    }): L.Circle;
    protected doRemoveCircle(circle: L.Circle): void;
    protected doCreateRectangle({ definition }: {
        definition: RectangleDefinition<RectangleOptions, PopupOptions>;
    }): L.Rectangle;
    protected doRemoveRectangle(rectangle: L.Rectangle): void;
    protected doCreateInfoWindow({ definition, element, }: {
        definition: Omit<InfoWindowDefinition<PopupOptions>, 'position'>;
        element: L.Marker | L.Polygon | L.Polyline | L.Circle | L.Rectangle;
    }): L.Popup;
    protected doCreateIcon({ definition, element }: {
        definition: Icon;
        element: L.Marker;
    }): void;
    protected doFitBoundsToMarkers(): void;
    private closePopups;
}
export {};
