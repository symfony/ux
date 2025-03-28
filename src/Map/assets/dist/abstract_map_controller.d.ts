import { Controller } from '@hotwired/stimulus';
export type Point = {
    lat: number;
    lng: number;
};
export type Identifier = string;
export type WithIdentifier<T extends Record<string, unknown>> = T & {
    '@id': Identifier;
};
export declare const IconTypes: {
    readonly Url: "url";
    readonly Svg: "svg";
    readonly UxIcon: "ux-icon";
};
export type Icon = {
    width: number;
    height: number;
} & ({
    type: typeof IconTypes.UxIcon;
    name: string;
    _generated_html: string;
} | {
    type: typeof IconTypes.Url;
    url: string;
} | {
    type: typeof IconTypes.Svg;
    html: string;
});
export type MarkerDefinition<MarkerOptions, InfoWindowOptions> = WithIdentifier<{
    position: Point;
    title: string | null;
    infoWindow?: InfoWindowWithoutPositionDefinition<InfoWindowOptions>;
    icon?: Icon;
    rawOptions?: MarkerOptions;
    extra: Record<string, unknown>;
}>;
export type PolygonDefinition<PolygonOptions, InfoWindowOptions> = WithIdentifier<{
    infoWindow?: InfoWindowWithoutPositionDefinition<InfoWindowOptions>;
    points: Array<Point>;
    title: string | null;
    rawOptions?: PolygonOptions;
    extra: Record<string, unknown>;
}>;
export type PolylineDefinition<PolylineOptions, InfoWindowOptions> = WithIdentifier<{
    infoWindow?: InfoWindowWithoutPositionDefinition<InfoWindowOptions>;
    points: Array<Point>;
    title: string | null;
    rawOptions?: PolylineOptions;
    extra: Record<string, unknown>;
}>;
export type InfoWindowDefinition<InfoWindowOptions> = {
    headerContent: string | null;
    content: string | null;
    position: Point;
    opened: boolean;
    autoClose: boolean;
    rawOptions?: InfoWindowOptions;
    extra: Record<string, unknown>;
};
export type InfoWindowWithoutPositionDefinition<InfoWindowOptions> = Omit<InfoWindowDefinition<InfoWindowOptions>, 'position'>;
export default abstract class<MapOptions, Map, MarkerOptions, Marker, InfoWindowOptions, InfoWindow, PolygonOptions, Polygon, PolylineOptions, Polyline> extends Controller<HTMLElement> {
    static values: {
        providerOptions: ObjectConstructor;
        center: ObjectConstructor;
        zoom: NumberConstructor;
        fitBoundsToMarkers: BooleanConstructor;
        markers: ArrayConstructor;
        polygons: ArrayConstructor;
        polylines: ArrayConstructor;
        options: ObjectConstructor;
    };
    centerValue: Point | null;
    zoomValue: number | null;
    fitBoundsToMarkersValue: boolean;
    markersValue: Array<MarkerDefinition<MarkerOptions, InfoWindowOptions>>;
    polygonsValue: Array<PolygonDefinition<PolygonOptions, InfoWindowOptions>>;
    polylinesValue: Array<PolylineDefinition<PolylineOptions, InfoWindowOptions>>;
    optionsValue: MapOptions;
    hasCenterValue: boolean;
    hasZoomValue: boolean;
    hasFitBoundsToMarkersValue: boolean;
    hasMarkersValue: boolean;
    hasPolygonsValue: boolean;
    hasPolylinesValue: boolean;
    hasOptionsValue: boolean;
    protected map: Map;
    protected markers: globalThis.Map<string, Marker>;
    protected polygons: globalThis.Map<string, Polygon>;
    protected polylines: globalThis.Map<string, Polyline>;
    protected infoWindows: Array<InfoWindow>;
    private isConnected;
    private createMarker;
    private createPolygon;
    private createPolyline;
    protected abstract dispatchEvent(name: string, payload: Record<string, unknown>): void;
    connect(): void;
    createInfoWindow({ definition, element, }: {
        definition: InfoWindowWithoutPositionDefinition<InfoWindowOptions>;
        element: Marker | Polygon | Polyline;
    }): InfoWindow;
    abstract centerValueChanged(): void;
    abstract zoomValueChanged(): void;
    markersValueChanged(): void;
    polygonsValueChanged(): void;
    polylinesValueChanged(): void;
    protected abstract doCreateMap({ center, zoom, options, }: {
        center: Point | null;
        zoom: number | null;
        options: MapOptions;
    }): Map;
    protected abstract doFitBoundsToMarkers(): void;
    protected abstract doCreateMarker({ definition, }: {
        definition: MarkerDefinition<MarkerOptions, InfoWindowOptions>;
    }): Marker;
    protected abstract doRemoveMarker(marker: Marker): void;
    protected abstract doCreatePolygon({ definition, }: {
        definition: PolygonDefinition<PolygonOptions, InfoWindowOptions>;
    }): Polygon;
    protected abstract doRemovePolygon(polygon: Polygon): void;
    protected abstract doCreatePolyline({ definition, }: {
        definition: PolylineDefinition<PolylineOptions, InfoWindowOptions>;
    }): Polyline;
    protected abstract doRemovePolyline(polyline: Polyline): void;
    protected abstract doCreateInfoWindow({ definition, element, }: {
        definition: InfoWindowWithoutPositionDefinition<InfoWindowOptions>;
        element: Marker | Polygon | Polyline;
    }): InfoWindow;
    protected abstract doCreateIcon({ definition, element, }: {
        definition: Icon;
        element: Marker;
    }): void;
    private createDrawingFactory;
    private onDrawChanged;
}
