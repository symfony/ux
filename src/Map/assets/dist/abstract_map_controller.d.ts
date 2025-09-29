import { Controller } from '@hotwired/stimulus';

type Point = {
    lat: number;
    lng: number;
};
type Identifier = string;
type WithIdentifier<T extends Record<string, unknown>> = T & {
    '@id': Identifier;
};
type ExtraData = Record<string, unknown>;
declare const IconTypes: {
    readonly Url: "url";
    readonly Svg: "svg";
    readonly UxIcon: "ux-icon";
};
type Icon = {
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
type MapDefinition<MapOptions, BridgeMapOptions> = {
    center: Point | null;
    zoom: number | null;
    minZoom: number | null;
    maxZoom: number | null;
    options: MapOptions;
    bridgeOptions?: BridgeMapOptions;
    extra: ExtraData;
};
type MarkerDefinition<BridgeMarkerOptions, BridgeInfoWindowOptions> = WithIdentifier<{
    position: Point;
    title: string | null;
    infoWindow?: Omit<InfoWindowDefinition<BridgeInfoWindowOptions>, 'position'>;
    icon?: Icon;
    rawOptions?: BridgeMarkerOptions;
    bridgeOptions?: BridgeMarkerOptions;
    extra: ExtraData;
}>;
type PolygonDefinition<BridgePolygonOptions, BridgeInfoWindowOptions> = WithIdentifier<{
    infoWindow?: Omit<InfoWindowDefinition<BridgeInfoWindowOptions>, 'position'>;
    points: Array<Point> | Array<Array<Point>>;
    title: string | null;
    rawOptions?: BridgePolygonOptions;
    bridgeOptions?: BridgePolygonOptions;
    extra: ExtraData;
}>;
type PolylineDefinition<BridgePolylineOptions, BridgeInfoWindowOptions> = WithIdentifier<{
    infoWindow?: Omit<InfoWindowDefinition<BridgeInfoWindowOptions>, 'position'>;
    points: Array<Point>;
    title: string | null;
    rawOptions?: BridgePolylineOptions;
    bridgeOptions?: BridgePolylineOptions;
    extra: ExtraData;
}>;
type CircleDefinition<BridgeCircleOptions, BridgeInfoWindowOptions> = WithIdentifier<{
    infoWindow?: Omit<InfoWindowDefinition<BridgeInfoWindowOptions>, 'position'>;
    center: Point;
    radius: number;
    title: string | null;
    rawOptions?: BridgeCircleOptions;
    bridgeOptions?: BridgeCircleOptions;
    extra: ExtraData;
}>;
type RectangleDefinition<BridgeRectangleOptions, BridgeInfoWindowOptions> = WithIdentifier<{
    infoWindow?: Omit<InfoWindowDefinition<BridgeInfoWindowOptions>, 'position'>;
    southWest: Point;
    northEast: Point;
    title: string | null;
    rawOptions?: BridgeRectangleOptions;
    bridgeOptions?: BridgeRectangleOptions;
    extra: ExtraData;
}>;
type InfoWindowDefinition<BridgeInfoWindowOptions> = {
    headerContent: string | null;
    content: string | null;
    position: Point;
    opened: boolean;
    autoClose: boolean;
    rawOptions?: BridgeInfoWindowOptions;
    bridgeOptions?: BridgeInfoWindowOptions;
    extra: ExtraData;
};
declare abstract class export_default<MapOptions, BridgeMapOptions, BridgeMap, BridgeMarkerOptions, BridgeMarker, BridgeInfoWindowOptions, BridgeInfoWindow, BridgePolygonOptions, BridgePolygon, BridgePolylineOptions, BridgePolyline, BridgeCircleOptions, BridgeCircle, BridgeRectangleOptions, BridgeRectangle> extends Controller<HTMLElement> {
    static values: {
        providerOptions: ObjectConstructor;
        center: ObjectConstructor;
        zoom: NumberConstructor;
        minZoom: NumberConstructor;
        maxZoom: NumberConstructor;
        fitBoundsToMarkers: BooleanConstructor;
        markers: ArrayConstructor;
        polygons: ArrayConstructor;
        polylines: ArrayConstructor;
        circles: ArrayConstructor;
        rectangles: ArrayConstructor;
        options: ObjectConstructor;
        extra: ObjectConstructor;
    };
    centerValue: Point | null;
    zoomValue: number | null;
    minZoomValue: number | null;
    maxZoomValue: number | null;
    fitBoundsToMarkersValue: boolean;
    markersValue: Array<MarkerDefinition<BridgeMarkerOptions, BridgeInfoWindowOptions>>;
    polygonsValue: Array<PolygonDefinition<BridgePolygonOptions, BridgeInfoWindowOptions>>;
    polylinesValue: Array<PolylineDefinition<BridgePolylineOptions, BridgeInfoWindowOptions>>;
    circlesValue: Array<CircleDefinition<BridgeCircleOptions, BridgeInfoWindowOptions>>;
    rectanglesValue: Array<RectangleDefinition<BridgeRectangleOptions, BridgeInfoWindowOptions>>;
    optionsValue: MapOptions;
    extraValue: Record<string, unknown>;
    hasCenterValue: boolean;
    hasZoomValue: boolean;
    hasMinZoomValue: boolean;
    hasMaxZoomValue: boolean;
    hasFitBoundsToMarkersValue: boolean;
    hasMarkersValue: boolean;
    hasPolygonsValue: boolean;
    hasPolylinesValue: boolean;
    hasCirclesValue: boolean;
    hasRectanglesValue: boolean;
    hasOptionsValue: boolean;
    hasExtraValue: boolean;
    protected map: BridgeMap;
    protected markers: Map<string, BridgeMarker>;
    protected polygons: Map<string, BridgePolygon>;
    protected polylines: Map<string, BridgePolyline>;
    protected circles: Map<string, BridgeCircle>;
    protected rectangles: Map<string, BridgeRectangle>;
    protected infoWindows: Array<BridgeInfoWindow>;
    private isConnected;
    private createMarker;
    private createPolygon;
    private createPolyline;
    private createCircle;
    private createRectangle;
    protected abstract dispatchEvent(name: string, payload: Record<string, unknown>): void;
    connect(): void;
    createInfoWindow({ definition, element, }: {
        definition: Omit<InfoWindowDefinition<BridgeInfoWindowOptions>, 'position'>;
        element: BridgeMarker | BridgePolygon | BridgePolyline | BridgeCircle | BridgeRectangle;
    }): BridgeInfoWindow;
    abstract centerValueChanged(): void;
    abstract zoomValueChanged(): void;
    abstract minZoomValueChanged(): void;
    abstract maxZoomValueChanged(): void;
    markersValueChanged(): void;
    polygonsValueChanged(): void;
    polylinesValueChanged(): void;
    circlesValueChanged(): void;
    rectanglesValueChanged(): void;
    protected abstract doCreateMap({ definition }: {
        definition: MapDefinition<MapOptions, BridgeMapOptions>;
    }): BridgeMap;
    protected abstract doFitBoundsToMarkers(): void;
    protected abstract doCreateMarker({ definition }: {
        definition: MarkerDefinition<BridgeMarkerOptions, BridgeInfoWindowOptions>;
    }): BridgeMarker;
    protected abstract doRemoveMarker(marker: BridgeMarker): void;
    protected abstract doCreatePolygon({ definition }: {
        definition: PolygonDefinition<BridgePolygonOptions, BridgeInfoWindowOptions>;
    }): BridgePolygon;
    protected abstract doRemovePolygon(polygon: BridgePolygon): void;
    protected abstract doCreatePolyline({ definition }: {
        definition: PolylineDefinition<BridgePolylineOptions, BridgeInfoWindowOptions>;
    }): BridgePolyline;
    protected abstract doRemovePolyline(polyline: BridgePolyline): void;
    protected abstract doCreateCircle({ definition }: {
        definition: CircleDefinition<BridgeCircleOptions, BridgeInfoWindowOptions>;
    }): BridgeCircle;
    protected abstract doRemoveCircle(circle: BridgeCircle): void;
    protected abstract doCreateRectangle({ definition }: {
        definition: RectangleDefinition<BridgeRectangleOptions, BridgeInfoWindowOptions>;
    }): BridgeRectangle;
    protected abstract doRemoveRectangle(rectangle: BridgeRectangle): void;
    protected abstract doCreateInfoWindow({ definition, element, }: {
        definition: Omit<InfoWindowDefinition<BridgeInfoWindowOptions>, 'position'>;
        element: BridgeMarker | BridgePolygon | BridgePolyline | BridgeCircle | BridgeRectangle;
    }): BridgeInfoWindow;
    protected abstract doCreateIcon({ definition, element }: {
        definition: Icon;
        element: BridgeMarker;
    }): void;
    private createDrawingFactory;
    private onDrawChanged;
}

export { type CircleDefinition, type Icon, IconTypes, type Identifier, type InfoWindowDefinition, type MapDefinition, type MarkerDefinition, type Point, type PolygonDefinition, type PolylineDefinition, type RectangleDefinition, type WithIdentifier, export_default as default };
