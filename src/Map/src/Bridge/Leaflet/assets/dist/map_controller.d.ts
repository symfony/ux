import AbstractMapController, { MapDefinition, MarkerDefinition, PolygonDefinition, PolylineDefinition, CircleDefinition, RectangleDefinition, InfoWindowDefinition, Icon } from '@symfony/ux-map';
import * as L from 'leaflet';
import { MapOptions as MapOptions$1, ControlPosition, MarkerOptions, PopupOptions, PolylineOptions, CircleOptions } from 'leaflet';

type MapOptions = Pick<MapOptions$1, 'attributionControl' | 'zoomControl'> & {
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
declare class export_default extends AbstractMapController<MapOptions, MapOptions$1, L.Map, MarkerOptions, L.Marker, PopupOptions, L.Popup, PolylineOptions, L.Polygon, PolylineOptions, L.Polyline, CircleOptions, L.Circle, PolylineOptions, L.Rectangle> {
    map: L.Map;
    connect(): void;
    centerValueChanged(): void;
    zoomValueChanged(): void;
    minZoomValueChanged(): void;
    maxZoomValueChanged(): void;
    protected dispatchEvent(name: string, payload?: Record<string, unknown>): void;
    protected doCreateMap({ definition }: {
        definition: MapDefinition<MapOptions, MapOptions$1>;
    }): L.Map;
    protected doCreateMarker({ definition }: {
        definition: MarkerDefinition<MarkerOptions, PopupOptions>;
    }): L.Marker;
    protected doRemoveMarker(marker: L.Marker): void;
    protected doCreatePolygon({ definition }: {
        definition: PolygonDefinition<PolylineOptions, PopupOptions>;
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
        definition: RectangleDefinition<PolylineOptions, PopupOptions>;
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

export { export_default as default };
