/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

export type MapDefinition<MapOptions, BridgeMapOptions> = {
    center: Point | null;
    zoom: number | null;
    options: MapOptions;
    /**
     * Additional options passed to the Map constructor.
     * These options are specific to the Map Bridge, and can be defined through `ux:map:pre-connect` event.
     */
    bridgeOptions?: BridgeMapOptions;
};

export type MarkerDefinition<BridgeMarkerOptions, BridgeInfoWindowOptions> = WithIdentifier<{
    position: Point;
    title: string | null;
    infoWindow?: Omit<InfoWindowDefinition<BridgeInfoWindowOptions>, 'position'>;
    icon?: Icon;
    /**
     * @deprecated Use "bridgeOptions" instead.
     * Raw options passed to the marker constructor, specific to the map provider (e.g.: `L.marker()` for Leaflet).
     */
    rawOptions?: BridgeMarkerOptions;
    /**
     * Additional options passed to the Marker constructor.
     * These options are specific to the Map Bridge, and can be defined through `ux:map:marker:before-create` event.
     */
    bridgeOptions?: BridgeMarkerOptions;
    /**
     * Extra data defined by the developer.
     * They are not directly used by the Stimulus controller, but they can be used by the developer with event listeners:
     *    - `ux:map:marker:before-create`
     *    - `ux:map:marker:after-create`
     */
    extra: Record<string, unknown>;
}>;

export type PolygonDefinition<BridgePolygonOptions, BridgeInfoWindowOptions> = WithIdentifier<{
    infoWindow?: Omit<InfoWindowDefinition<BridgeInfoWindowOptions>, 'position'>;
    points: Array<Point> | Array<Array<Point>>;
    title: string | null;
    /**
     * @deprecated Use "bridgeOptions" instead.
     * Raw options passed to the polygon constructor, specific to the map provider (e.g.: `L.polygon()` for Leaflet).
     */
    rawOptions?: BridgePolygonOptions;
    /**
     * Additional options passed to the Polygon constructor.
     * These options are specific to the Map Bridge, and can be defined through `ux:map:polygon:before-create` event.
     */
    bridgeOptions?: BridgePolygonOptions;
    /**
     * Extra data defined by the developer.
     * They are not directly used by the Stimulus controller, but they can be used by the developer with event listeners:
     *    - `ux:map:polygon:before-create`
     *    - `ux:map:polygon:after-create`
     */
    extra: Record<string, unknown>;
}>;

export type PolylineDefinition<BridgePolylineOptions, BridgeInfoWindowOptions> = WithIdentifier<{
    infoWindow?: Omit<InfoWindowDefinition<BridgeInfoWindowOptions>, 'position'>;
    points: Array<Point>;
    title: string | null;
    /**
     * @deprecated Use "bridgeOptions" instead.
     * Raw options passed to the polyline constructor, specific to the map provider (e.g.: `L.polyline()` for Leaflet).
     */
    rawOptions?: BridgePolylineOptions;
    /**
     * Additional options passed to the Polyline constructor.
     * These options are specific to the Map Bridge, and can be defined through `ux:map:polyline:before-create` event.
     */
    bridgeOptions?: BridgePolylineOptions;
    /**
     * Extra data defined by the developer.
     * They are not directly used by the Stimulus controller, but they can be used by the developer with event listeners:
     *    - `ux:map:polyline:before-create`
     *    - `ux:map:polyline:after-create`
     */
    extra: Record<string, unknown>;
}>;

export type CircleDefinition<BridgeCircleOptions, BridgeInfoWindowOptions> = WithIdentifier<{
    infoWindow?: Omit<InfoWindowDefinition<BridgeInfoWindowOptions>, 'position'>;
    center: Point;
    radius: number;
    title: string | null;
    /**
     * @deprecated Use "bridgeOptions" instead.
     * Raw options passed to the circle constructor, specific to the map provider (e.g.: `L.circle()` for Leaflet).
     */
    rawOptions?: BridgeCircleOptions;
    /**
     * Additional options passed to the Circle constructor.
     * These options are specific to the Map Bridge, and can be defined through `ux:map:circle:before-create` event.
     */
    bridgeOptions?: BridgeCircleOptions;
    /**
     * Extra data defined by the developer.
     * They are not directly used by the Stimulus controller, but they can be used by the developer with event listeners:
     *    - `ux:map:circle:before-create`
     *    - `ux:map:circle:after-create`
     */
    extra: Record<string, unknown>;
}>;

export type RectangleDefinition<BridgeRectangleOptions, BridgeInfoWindowOptions> = WithIdentifier<{
    infoWindow?: Omit<InfoWindowDefinition<BridgeInfoWindowOptions>, 'position'>;
    southWest: Point;
    northEast: Point;
    title: string | null;
    /**
     * @deprecated Use "bridgeOptions" instead.
     * Raw options passed to the rectangle constructor, specific to the map provider (e.g.: `L.rectangle()` for Leaflet).
     */
    rawOptions?: BridgeRectangleOptions;
    /**
     * Additional options passed to the Rectangle constructor.
     * These options are specific to the Map Bridge, and can be defined through `ux:map:rectangle:before-create` event.
     */
    bridgeOptions?: BridgeRectangleOptions;
    /**
     * Extra data defined by the developer.
     * They are not directly used by the Stimulus controller, but they can be used by the developer with event listeners:
     *    - `ux:map:rectangle:before-create`
     *    - `ux:map:rectangle:after-create`
     */
    extra: Record<string, unknown>;
}>;

export type InfoWindowDefinition<BridgeInfoWindowOptions> = {
    headerContent: string | null;
    content: string | null;
    position: Point;
    opened: boolean;
    autoClose: boolean;
    /**
     * @deprecated Use "bridgeOptions" instead.
     * Raw options passed to the info window constructor, specific to the map provider (e.g.: `google.maps.InfoWindow()` for Google Maps).
     */
    rawOptions?: BridgeInfoWindowOptions;
    /**
     * Additional options passed to the InfoWindow constructor.
     * These options are specific to the Map Bridge, and can be defined through `ux:map:info-window:before-create` event.
     */
    bridgeOptions?: BridgeInfoWindowOptions;
    /**
     * Extra data defined by the developer.
     * They are not directly used by the Stimulus controller, but they can be used by the developer with event listeners:
     *    - `ux:map:info-window:before-create`
     *    - `ux:map:info-window:after-create`
     */
    extra: Record<string, unknown>;
};

export default abstract class<
    MapOptions, // Normalized `*Options` PHP class from Bridge (to not be confused with the JS Map class options)
    BridgeMapOptions, // The options for the JavaScript Map class from Bridge
    BridgeMap, // The JavaScript Map class from Bridge (e.g.: `L.Map` for Leaflet, `google.maps.Map` for Google Maps)
    BridgeMarkerOptions, // The options for the JavaScript Marker class from Bridge
    BridgeMarker, // The JavaScript Marker class from Bridge
    BridgeInfoWindowOptions, // The options for the JavaScript InfoWindow class from Bridge
    BridgeInfoWindow, // The JavaScript InfoWindow class from Bridge
    BridgePolygonOptions, // The options for the JavaScript Polygon class from Bridge
    BridgePolygon, // The JavaScript Polygon class from Bridge
    BridgePolylineOptions, // The options for the JavaScript Polyline class from Bridge
    BridgePolyline, // The JavaScript Polyline class from Bridge
    BridgeCircleOptions, // The options for the JavaScript Circle class from Bridge
    BridgeCircle, // The JavaScript Circle class from Bridge
    BridgeRectangleOptions, // The options for the JavaScript Rectangle class from Bridge
    BridgeRectangle, // The JavaScript Rectangle class from Bridge
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
    declare markersValue: Array<MarkerDefinition<BridgeMarkerOptions, BridgeInfoWindowOptions>>;
    declare polygonsValue: Array<PolygonDefinition<BridgePolygonOptions, BridgeInfoWindowOptions>>;
    declare polylinesValue: Array<PolylineDefinition<BridgePolylineOptions, BridgeInfoWindowOptions>>;
    declare circlesValue: Array<CircleDefinition<BridgeCircleOptions, BridgeInfoWindowOptions>>;
    declare rectanglesValue: Array<RectangleDefinition<BridgeRectangleOptions, BridgeInfoWindowOptions>>;
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

    protected map: BridgeMap;
    protected markers = new Map<Identifier, BridgeMarker>();
    protected polygons = new Map<Identifier, BridgePolygon>();
    protected polylines = new Map<Identifier, BridgePolyline>();
    protected circles = new Map<Identifier, BridgeCircle>();
    protected rectangles = new Map<Identifier, BridgeRectangle>();
    protected infoWindows: Array<BridgeInfoWindow> = [];

    private isConnected = false;
    private createMarker: ({ definition }: { definition: MarkerDefinition<BridgeMarkerOptions, BridgeInfoWindowOptions> }) => BridgeMarker;
    private createPolygon: ({ definition }: { definition: PolygonDefinition<BridgePolygonOptions, BridgeInfoWindowOptions> }) => BridgePolygon;
    private createPolyline: ({ definition }: { definition: PolylineDefinition<BridgePolylineOptions, BridgeInfoWindowOptions> }) => BridgePolyline;
    private createCircle: ({ definition }: { definition: CircleDefinition<BridgeCircleOptions, BridgeInfoWindowOptions> }) => BridgeCircle;
    private createRectangle: ({ definition }: { definition: RectangleDefinition<BridgeRectangleOptions, BridgeInfoWindowOptions> }) => BridgeRectangle;

    protected abstract dispatchEvent(name: string, payload: Record<string, unknown>): void;

    connect() {
        const mapDefinition: MapDefinition<MapOptions, BridgeMapOptions> = {
            center: this.hasCenterValue ? this.centerValue : null,
            zoom: this.hasZoomValue ? this.zoomValue : null,
            options: this.optionsValue,
        };
        this.dispatchEvent('pre-connect', mapDefinition);

        this.createMarker = this.createDrawingFactory('marker', this.markers, this.doCreateMarker.bind(this));
        this.createPolygon = this.createDrawingFactory('polygon', this.polygons, this.doCreatePolygon.bind(this));
        this.createPolyline = this.createDrawingFactory('polyline', this.polylines, this.doCreatePolyline.bind(this));
        this.createCircle = this.createDrawingFactory('circle', this.circles, this.doCreateCircle.bind(this));
        this.createRectangle = this.createDrawingFactory('rectangle', this.rectangles, this.doCreateRectangle.bind(this));

        this.map = this.doCreateMap({ definition: mapDefinition });
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
        definition: Omit<InfoWindowDefinition<BridgeInfoWindowOptions>, 'position'>;
        element: BridgeMarker | BridgePolygon | BridgePolyline | BridgeCircle | BridgeRectangle;
    }): BridgeInfoWindow {
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
    protected abstract doCreateMap({ definition }: { definition: MapDefinition<MapOptions, BridgeMapOptions> }): BridgeMap;

    protected abstract doFitBoundsToMarkers(): void;

    protected abstract doCreateMarker({ definition }: { definition: MarkerDefinition<BridgeMarkerOptions, BridgeInfoWindowOptions> }): BridgeMarker;

    protected abstract doRemoveMarker(marker: BridgeMarker): void;

    protected abstract doCreatePolygon({ definition }: { definition: PolygonDefinition<BridgePolygonOptions, BridgeInfoWindowOptions> }): BridgePolygon;

    protected abstract doRemovePolygon(polygon: BridgePolygon): void;

    protected abstract doCreatePolyline({ definition }: { definition: PolylineDefinition<BridgePolylineOptions, BridgeInfoWindowOptions> }): BridgePolyline;

    protected abstract doRemovePolyline(polyline: BridgePolyline): void;

    protected abstract doCreateCircle({ definition }: { definition: CircleDefinition<BridgeCircleOptions, BridgeInfoWindowOptions> }): BridgeCircle;

    protected abstract doRemoveCircle(circle: BridgeCircle): void;

    protected abstract doCreateRectangle({ definition }: { definition: RectangleDefinition<BridgeRectangleOptions, BridgeInfoWindowOptions> }): BridgeRectangle;

    protected abstract doRemoveRectangle(rectangle: BridgeRectangle): void;

    protected abstract doCreateInfoWindow({
        definition,
        element,
    }: {
        definition: Omit<InfoWindowDefinition<BridgeInfoWindowOptions>, 'position'>;
        element: BridgeMarker | BridgePolygon | BridgePolyline | BridgeCircle | BridgeRectangle;
    }): BridgeInfoWindow;
    protected abstract doCreateIcon({ definition, element }: { definition: Icon; element: BridgeMarker }): void;

    //endregion

    //region Private APIs
    private createDrawingFactory(type: 'marker', draws: typeof this.markers, factory: typeof this.doCreateMarker): typeof this.doCreateMarker;
    private createDrawingFactory(type: 'polygon', draws: typeof this.polygons, factory: typeof this.doCreatePolygon): typeof this.doCreatePolygon;
    private createDrawingFactory(type: 'polyline', draws: typeof this.polylines, factory: typeof this.doCreatePolyline): typeof this.doCreatePolyline;
    private createDrawingFactory(type: 'circle', draws: typeof this.circles, factory: typeof this.doCreateCircle): typeof this.doCreateCircle;
    private createDrawingFactory(type: 'rectangle', draws: typeof this.rectangles, factory: typeof this.doCreateRectangle): typeof this.doCreateRectangle;
    private createDrawingFactory<
        Factory extends
            | typeof this.doCreateMarker
            | typeof this.doCreatePolygon
            | typeof this.doCreatePolyline
            | typeof this.doCreateCircle
            | typeof this.doCreateRectangle,
        Draw extends ReturnType<Factory>,
    >(type: 'marker' | 'polygon' | 'polyline' | 'circle' | 'rectangle', draws: globalThis.Map<WithIdentifier<any>, Draw>, factory: Factory): Factory {
        const eventBefore = `${type}:before-create`;
        const eventAfter = `${type}:after-create`;

        // @ts-expect-error IDK what to do with this error
        // 'Factory' could be instantiated with an arbitrary type which could be unrelated to '({ definition }: { definition: WithIdentifier<any>; }) => Draw'
        return ({ definition }: { definition: WithIdentifier<any> }) => {
            this.dispatchEvent(eventBefore, { definition });

            if (typeof definition.rawOptions !== 'undefined') {
                console.warn(
                    `[Symfony UX Map] The event "${eventBefore}" added a deprecated "rawOptions" property to the definition, it will be removed in a next major version, replace it with "bridgeOptions" instead.`,
                    definition
                );
            }

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
