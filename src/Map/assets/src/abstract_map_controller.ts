import { Controller } from '@hotwired/stimulus';

export type Point = { lat: number; lng: number };
export type Identifier = string;
export type WithIdentifier<T extends Record<string, unknown>> = T & { '@id': Identifier };

export const IconTypes = {
    Url: 'url',
    Svg: 'svg',
    UxIcon: 'ux-icon',
} as const;
export type Icon = {
    width: number;
    height: number;
} & (
    | {
          type: typeof IconTypes.UxIcon;
          name: string;
          _generated_html: string;
      }
    | {
          type: typeof IconTypes.Url;
          url: string;
      }
    | {
          type: typeof IconTypes.Svg;
          html: string;
      }
);

export type MarkerDefinition<MarkerOptions, InfoWindowOptions> = WithIdentifier<{
    position: Point;
    title: string | null;
    infoWindow?: InfoWindowWithoutPositionDefinition<InfoWindowOptions>;
    icon?: Icon;
    /**
     * Raw options passed to the marker constructor, specific to the map provider (e.g.: `L.marker()` for Leaflet).
     */
    rawOptions?: MarkerOptions;
    /**
     * Extra data defined by the developer.
     * They are not directly used by the Stimulus controller, but they can be used by the developer with event listeners:
     *    - `ux:map:marker:before-create`
     *    - `ux:map:marker:after-create`
     */
    extra: Record<string, unknown>;
}>;

export type PolygonDefinition<PolygonOptions, InfoWindowOptions> = WithIdentifier<{
    infoWindow?: InfoWindowWithoutPositionDefinition<InfoWindowOptions>;
    points: Array<Point> | Array<Array<Point>>;
    title: string | null;
    /**
     * Raw options passed to the marker constructor, specific to the map provider (e.g.: `L.marker()` for Leaflet).
     */
    rawOptions?: PolygonOptions;
    /**
     * Extra data defined by the developer.
     * They are not directly used by the Stimulus controller, but they can be used by the developer with event listeners:
     *    - `ux:map:polygon:before-create`
     *    - `ux:map:polygon:after-create`
     */
    extra: Record<string, unknown>;
}>;

export type PolylineDefinition<PolylineOptions, InfoWindowOptions> = WithIdentifier<{
    infoWindow?: InfoWindowWithoutPositionDefinition<InfoWindowOptions>;
    points: Array<Point>;
    title: string | null;
    /**
     * Raw options passed to the marker constructor, specific to the map provider (e.g.: `L.marker()` for Leaflet).
     */
    rawOptions?: PolylineOptions;
    /**
     * Extra data defined by the developer.
     * They are not directly used by the Stimulus controller, but they can be used by the developer with event listeners:
     *    - `ux:map:polyline:before-create`
     *    - `ux:map:polyline:after-create`
     */
    extra: Record<string, unknown>;
}>;

export type CircleDefinition<CircleOptions, InfoWindowOptions> = WithIdentifier<{
    infoWindow?: InfoWindowWithoutPositionDefinition<InfoWindowOptions>;
    center: Point;
    radius: number;
    title: string | null;
    /**
     * Raw options passed to the circle constructor, specific to the map provider (e.g.: `L.circle()` for Leaflet).
     */
    rawOptions?: CircleOptions;
    /**
     * Extra data defined by the developer.
     * They are not directly used by the Stimulus controller, but they can be used by the developer with event listeners:
     *    - `ux:map:circle:before-create`
     *    - `ux:map:circle:after-create`
     */
    extra: Record<string, unknown>;
}>;

export type RectangleDefinition<RectangleOptions, InfoWindowOptions> = WithIdentifier<{
    infoWindow?: InfoWindowWithoutPositionDefinition<InfoWindowOptions>;
    bounds: { northEast: Point; southWest: Point };
    title: string | null;
    /**
     * Raw options passed to the rectangle constructor, specific to the map provider (e.g.: `L.rectangle()` for Leaflet).
     */
    rawOptions?: RectangleOptions;
    /**
     * Extra data defined by the developer.
     * They are not directly used by the Stimulus controller, but they can be used by the developer with event listeners:
     *    - `ux:map:rectangle:before-create`
     *    - `ux:map:rectangle:after-create`
     */
    extra: Record<string, unknown>;
}>;

export type InfoWindowDefinition<InfoWindowOptions> = {
    headerContent: string | null;
    content: string | null;
    position: Point;
    opened: boolean;
    autoClose: boolean;
    /**
     * Raw options passed to the info window constructor,
     * specific to the map provider (e.g.: `google.maps.InfoWindow()` for Google Maps).
     */
    rawOptions?: InfoWindowOptions;
    /**
     * Extra data defined by the developer.
     * They are not directly used by the Stimulus controller, but they can be used by the developer with event listeners:
     *    - `ux:map:info-window:before-create`
     *    - `ux:map:info-window:after-create`
     */
    extra: Record<string, unknown>;
};

export type InfoWindowWithoutPositionDefinition<InfoWindowOptions> = Omit<
    InfoWindowDefinition<InfoWindowOptions>,
    'position'
>;

export default abstract class<
    MapOptions,
    Map,
    MarkerOptions,
    Marker,
    InfoWindowOptions,
    InfoWindow,
    PolygonOptions,
    Polygon,
    PolylineOptions,
    Polyline,
    CircleOptions,
    Circle,
    RectangleOptions,
    Rectangle,
> extends Controller<HTMLElement> {
    static values = {
        providerOptions: Object,
        center: Object,
        zoom: Number,
        fitBoundsToMarkers: Boolean,
        markers: Array,
        polygons: Array,
        polylines: Array,
        circles: Array,
        rectangles: Array,
        options: Object,
    };

    declare centerValue: Point | null;
    declare zoomValue: number | null;
    declare fitBoundsToMarkersValue: boolean;
    declare markersValue: Array<MarkerDefinition<MarkerOptions, InfoWindowOptions>>;
    declare polygonsValue: Array<PolygonDefinition<PolygonOptions, InfoWindowOptions>>;
    declare polylinesValue: Array<PolylineDefinition<PolylineOptions, InfoWindowOptions>>;
    declare circlesValue: Array<CircleDefinition<CircleOptions, InfoWindowOptions>>;
    declare rectanglesValue: Array<RectangleDefinition<RectangleOptions, InfoWindowOptions>>;
    declare optionsValue: MapOptions;

    declare hasCenterValue: boolean;
    declare hasZoomValue: boolean;
    declare hasFitBoundsToMarkersValue: boolean;
    declare hasMarkersValue: boolean;
    declare hasPolygonsValue: boolean;
    declare hasPolylinesValue: boolean;
    declare hasCirclesValue: boolean;
    declare hasRectanglesValue: boolean;
    declare hasOptionsValue: boolean;

    protected map: Map;
    protected markers = new Map<Identifier, Marker>();
    protected polygons = new Map<Identifier, Polygon>();
    protected polylines = new Map<Identifier, Polyline>();
    protected circles = new Map<Identifier, Circle>();
    protected rectangles = new Map<Identifier, Rectangle>();
    protected infoWindows: Array<InfoWindow> = [];

    private isConnected = false;
    private createMarker: ({
        definition,
    }: { definition: MarkerDefinition<MarkerOptions, InfoWindowOptions> }) => Marker;
    private createPolygon: ({
        definition,
    }: { definition: PolygonDefinition<PolygonOptions, InfoWindowOptions> }) => Polygon;
    private createPolyline: ({
        definition,
    }: { definition: PolylineDefinition<PolylineOptions, InfoWindowOptions> }) => Polyline;
    private createCircle: ({
        definition,
    }: { definition: CircleDefinition<CircleOptions, InfoWindowOptions> }) => Circle;
    private createRectangle: ({
        definition,
    }: { definition: RectangleDefinition<RectangleOptions, InfoWindowOptions> }) => Rectangle;

    protected abstract dispatchEvent(name: string, payload: Record<string, unknown>): void;

    connect() {
        const options = this.optionsValue;

        this.dispatchEvent('pre-connect', { options });

        this.createMarker = this.createDrawingFactory('marker', this.markers, this.doCreateMarker.bind(this));
        this.createPolygon = this.createDrawingFactory('polygon', this.polygons, this.doCreatePolygon.bind(this));
        this.createPolyline = this.createDrawingFactory('polyline', this.polylines, this.doCreatePolyline.bind(this));
        this.createCircle = this.createDrawingFactory('circle', this.circles, this.doCreateCircle.bind(this));
        this.createRectangle = this.createDrawingFactory(
            'rectangle',
            this.rectangles,
            this.doCreateRectangle.bind(this)
        );

        this.map = this.doCreateMap({
            center: this.hasCenterValue ? this.centerValue : null,
            zoom: this.hasZoomValue ? this.zoomValue : null,
            options,
        });
        this.markersValue.forEach((definition) => this.createMarker({ definition }));
        this.polygonsValue.forEach((definition) => this.createPolygon({ definition }));
        this.polylinesValue.forEach((definition) => this.createPolyline({ definition }));
        this.circlesValue.forEach((definition) => this.createCircle({ definition }));
        this.rectanglesValue.forEach((definition) => this.createRectangle({ definition }));

        if (this.fitBoundsToMarkersValue) {
            this.doFitBoundsToMarkers();
        }

        this.dispatchEvent('connect', {
            map: this.map,
            markers: [...this.markers.values()],
            polygons: [...this.polygons.values()],
            polylines: [...this.polylines.values()],
            circles: [...this.circles.values()],
            rectangles: [...this.rectangles.values()],
            infoWindows: this.infoWindows,
        });

        this.isConnected = true;
    }

    //region Public API
    public createInfoWindow({
        definition,
        element,
    }: {
        definition: InfoWindowWithoutPositionDefinition<InfoWindowOptions>;
        element: Marker | Polygon | Polyline | Circle | Rectangle;
    }): InfoWindow {
        this.dispatchEvent('info-window:before-create', { definition, element });
        const infoWindow = this.doCreateInfoWindow({ definition, element });
        this.dispatchEvent('info-window:after-create', { infoWindow, definition, element });

        this.infoWindows.push(infoWindow);

        return infoWindow;
    }

    //endregion

    //region Hooks called by Stimulus when the values change
    public abstract centerValueChanged(): void;

    public abstract zoomValueChanged(): void;

    public markersValueChanged(): void {
        if (!this.isConnected) {
            return;
        }

        this.onDrawChanged(this.markers, this.markersValue, this.createMarker, this.doRemoveMarker);

        if (this.fitBoundsToMarkersValue) {
            this.doFitBoundsToMarkers();
        }
    }

    public polygonsValueChanged(): void {
        if (!this.isConnected) {
            return;
        }

        this.onDrawChanged(this.polygons, this.polygonsValue, this.createPolygon, this.doRemovePolygon);
    }

    public polylinesValueChanged(): void {
        if (!this.isConnected) {
            return;
        }

        this.onDrawChanged(this.polylines, this.polylinesValue, this.createPolyline, this.doRemovePolyline);
    }

    public circlesValueChanged(): void {
        if (!this.isConnected) {
            return;
        }

        this.onDrawChanged(this.circles, this.circlesValue, this.createCircle, this.doRemoveCircle);
    }

    public rectanglesValueChanged(): void {
        if (!this.isConnected) {
            return;
        }

        this.onDrawChanged(this.rectangles, this.rectanglesValue, this.createRectangle, this.doRemoveRectangle);
    }

    //endregion

    //region Abstract factory methods to be implemented by the concrete classes, they are specific to the map provider
    protected abstract doCreateMap({
        center,
        zoom,
        options,
    }: {
        center: Point | null;
        zoom: number | null;
        options: MapOptions;
    }): Map;

    protected abstract doFitBoundsToMarkers(): void;

    protected abstract doCreateMarker({
        definition,
    }: { definition: MarkerDefinition<MarkerOptions, InfoWindowOptions> }): Marker;

    protected abstract doRemoveMarker(marker: Marker): void;

    protected abstract doCreatePolygon({
        definition,
    }: {
        definition: PolygonDefinition<PolygonOptions, InfoWindowOptions>;
    }): Polygon;

    protected abstract doRemovePolygon(polygon: Polygon): void;

    protected abstract doCreatePolyline({
        definition,
    }: {
        definition: PolylineDefinition<PolylineOptions, InfoWindowOptions>;
    }): Polyline;

    protected abstract doRemovePolyline(polyline: Polyline): void;

    protected abstract doCreateCircle({
        definition,
    }: {
        definition: CircleDefinition<CircleOptions, InfoWindowOptions>;
    }): Circle;

    protected abstract doRemoveCircle(circle: Circle): void;

    protected abstract doCreateRectangle({
        definition,
    }: {
        definition: RectangleDefinition<RectangleOptions, InfoWindowOptions>;
    }): Rectangle;

    protected abstract doRemoveRectangle(rectangle: Rectangle): void;

    protected abstract doCreateInfoWindow({
        definition,
        element,
    }: {
        definition: InfoWindowWithoutPositionDefinition<InfoWindowOptions>;
        element: Marker | Polygon | Polyline | Circle | Rectangle;
    }): InfoWindow;
    protected abstract doCreateIcon({
        definition,
        element,
    }: {
        definition: Icon;
        element: Marker;
    }): void;

    //endregion

    //region Private APIs
    private createDrawingFactory(
        type: 'marker',
        draws: typeof this.markers,
        factory: typeof this.doCreateMarker
    ): typeof this.doCreateMarker;
    private createDrawingFactory(
        type: 'polygon',
        draws: typeof this.polygons,
        factory: typeof this.doCreatePolygon
    ): typeof this.doCreatePolygon;
    private createDrawingFactory(
        type: 'polyline',
        draws: typeof this.polylines,
        factory: typeof this.doCreatePolyline
    ): typeof this.doCreatePolyline;
    private createDrawingFactory(
        type: 'circle',
        draws: typeof this.circles,
        factory: typeof this.doCreateCircle
    ): typeof this.doCreateCircle;
    private createDrawingFactory(
        type: 'rectangle',
        draws: typeof this.rectangles,
        factory: typeof this.doCreateRectangle
    ): typeof this.doCreateRectangle;
    private createDrawingFactory<
        Factory extends
            | typeof this.doCreateMarker
            | typeof this.doCreatePolygon
            | typeof this.doCreatePolyline
            | typeof this.doCreateCircle
            | typeof this.doCreateRectangle,
        Draw extends ReturnType<Factory>,
    >(
        type: 'marker' | 'polygon' | 'polyline' | 'circle' | 'rectangle',
        draws: globalThis.Map<WithIdentifier<any>, Draw>,
        factory: Factory
    ): Factory {
        const eventBefore = `${type}:before-create`;
        const eventAfter = `${type}:after-create`;

        // @ts-expect-error IDK what to do with this error
        // 'Factory' could be instantiated with an arbitrary type which could be unrelated to '({ definition }: { definition: WithIdentifier<any>; }) => Draw'
        return ({ definition }: { definition: WithIdentifier<any> }) => {
            this.dispatchEvent(eventBefore, { definition });
            const drawing = factory({ definition }) as Draw;
            this.dispatchEvent(eventAfter, { [type]: drawing, definition });

            draws.set(definition['@id'], drawing);

            return drawing;
        };
    }

    private onDrawChanged(
        draws: typeof this.markers,
        newDrawDefinitions: typeof this.markersValue,
        factory: typeof this.createMarker,
        remover: typeof this.doRemoveMarker
    ): void;
    private onDrawChanged(
        draws: typeof this.polygons,
        newDrawDefinitions: typeof this.polygonsValue,
        factory: typeof this.createPolygon,
        remover: typeof this.doRemovePolygon
    ): void;
    private onDrawChanged(
        draws: typeof this.polylines,
        newDrawDefinitions: typeof this.polylinesValue,
        factory: typeof this.createPolyline,
        remover: typeof this.doRemovePolyline
    ): void;
    private onDrawChanged(
        draws: typeof this.circles,
        newDrawDefinitions: typeof this.circlesValue,
        factory: typeof this.createCircle,
        remover: typeof this.doRemoveCircle
    ): void;
    private onDrawChanged(
        draws: typeof this.rectangles,
        newDrawDefinitions: typeof this.rectanglesValue,
        factory: typeof this.createRectangle,
        remover: typeof this.doRemoveRectangle
    ): void;
    private onDrawChanged<Draw, DrawDefinition extends WithIdentifier<Record<string, unknown>>>(
        draws: globalThis.Map<WithIdentifier<any>, Draw>,
        newDrawDefinitions: Array<DrawDefinition>,
        factory: (args: { definition: DrawDefinition }) => Draw,
        remover: (args: Draw) => void
    ): void {
        const idsToRemove = new Set(draws.keys());
        newDrawDefinitions.forEach((definition) => {
            idsToRemove.delete(definition['@id']);
        });

        idsToRemove.forEach((id) => {
            // biome-ignore lint/style/noNonNullAssertion: the ids are coming from the keys of the map
            const draw = draws.get(id)!;
            remover(draw);
            draws.delete(id);
        });

        newDrawDefinitions.forEach((definition) => {
            if (!draws.has(definition['@id'])) {
                factory({ definition });
            }
        });
    }
    //endregion
}
