/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import type {
    Circle,
    CircleOptions,
    Control,
    ControlOptions,
    DivIcon,
    DivIconOptions,
    Icon,
    IconOptions,
    LatLngBounds,
    LatLngBoundsExpression,
    LatLngBoundsLiteral,
    LatLngExpression,
    Map as LMap,
    MapOptions as LeafletMapOptions,
    Marker,
    MarkerOptions,
    Polygon,
    Polyline,
    PolylineOptions,
    Popup,
    PopupOptions,
    Rectangle,
    TileLayer,
    TileLayerOptions,
} from 'leaflet';

/**
 * The `leaflet` module shape, left loose because v1 and v2 expose subtly
 * different keys (v1 has lowercase factory functions, v2 exposes only the
 * named class exports).
 */
type LeafletModule = Record<string, any>;

/**
 * The legacy (v1-flavored) namespace we expose as `event.detail.L` so that
 * existing consumer code written against v1 keeps working under v2.
 */
export interface LeafletV1Namespace {
    map: (...args: any[]) => LMap;
    tileLayer: (...args: any[]) => TileLayer;
    marker: (...args: any[]) => Marker;
    icon: (...args: any[]) => Icon;
    divIcon: (...args: any[]) => DivIcon;
    popup: (...args: any[]) => Popup;
    polygon: (...args: any[]) => Polygon;
    polyline: (...args: any[]) => Polyline;
    circle: (...args: any[]) => Circle;
    rectangle: (...args: any[]) => Rectangle;
    latLngBounds: (...args: any[]) => LatLngBounds;
    control: ((options?: ControlOptions) => Control) & {
        attribution: (...args: any[]) => Control.Attribution;
        zoom: (...args: any[]) => Control.Zoom;
        layers: (...args: any[]) => Control.Layers;
        scale: (...args: any[]) => Control.Scale;
    };
    Map: new (...args: any[]) => LMap;
    Marker: new (...args: any[]) => Marker;
    TileLayer: new (...args: any[]) => TileLayer;
    Icon: (new (...args: any[]) => Icon) & { Default: new (...args: any[]) => Icon };
    DivIcon: new (...args: any[]) => DivIcon;
    Popup: new (...args: any[]) => Popup;
    Polygon: new (...args: any[]) => Polygon;
    Polyline: new (...args: any[]) => Polyline;
    Circle: new (...args: any[]) => Circle;
    Rectangle: new (...args: any[]) => Rectangle;
    [key: string]: unknown;
}

/**
 * Uniform surface used inside `map_controller.ts`. Hides the v1/v2
 * discriminator from callers.
 */
export interface LeafletAdapter {
    createMap(container: HTMLElement, options: LeafletMapOptions): LMap;
    createTileLayer(url: string, options: TileLayerOptions): TileLayer;
    createMarker(latlng: LatLngExpression, options: MarkerOptions): Marker;
    createIcon(options: IconOptions): Icon;
    createDivIcon(options: DivIconOptions): DivIcon;
    createPopup(options: PopupOptions): Popup;
    createPolygon(latlngs: LatLngExpression[] | LatLngExpression[][], options: PolylineOptions): Polygon;
    createPolyline(latlngs: LatLngExpression[], options: PolylineOptions): Polyline;
    createCircle(latlng: LatLngExpression, options: CircleOptions): Circle;
    createRectangle(bounds: LatLngBoundsExpression | LatLngBoundsLiteral, options: PolylineOptions): Rectangle;
    createAttributionControl(options: Control.AttributionOptions): Control.Attribution;
    createZoomControl(options: Control.ZoomOptions): Control.Zoom;
    /**
     * The namespace to stamp onto `event.detail.L`. In v1 this is the real
     * imported module (zero-cost passthrough). In v2 this is a synthesized
     * compat namespace that preserves the v1 contract for consumer code.
     */
    readonly legacyNamespace: LeafletV1Namespace;
}

/**
 * Returns the detected major version of the given `leaflet` module.
 *
 * Both 1.9.x and 2.x expose `version` as a string (e.g. `"1.9.4"` or
 * `"2.0.0"`). We parse the leading integer and fall back to v1 when the
 * value is missing or unparsable — v1 has always exported it.
 */
export function detectVersion(mod: LeafletModule): 1 | 2 {
    const major = parseInt(String((mod as any)?.version ?? ''), 10);
    return major >= 2 ? 2 : 1;
}

function buildLegacyNamespace(mod: LeafletModule): LeafletV1Namespace {
    const m = mod as any;

    const factory =
        <TCtor extends new (...args: any[]) => any>(Ctor: TCtor) =>
        (...args: ConstructorParameters<TCtor>): InstanceType<TCtor> =>
            new Ctor(...args);

    const control = ((options?: ControlOptions) => new m.Control(options)) as LeafletV1Namespace['control'];
    control.attribution = factory(m.Control.Attribution);
    control.zoom = factory(m.Control.Zoom);
    control.layers = factory(m.Control.Layers);
    control.scale = factory(m.Control.Scale);

    return {
        // Pass through anything else v2 exports (LatLng, Point, Bounds,
        // Layer, Util, DomEvent, ...) so consumer code touching them keeps
        // working. Placed first so explicit keys below always win.
        ...m,
        map: (container: HTMLElement | string, options?: LeafletMapOptions) => new m.Map(container, options),
        tileLayer: factory(m.TileLayer),
        marker: factory(m.Marker),
        icon: factory(m.Icon),
        divIcon: factory(m.DivIcon),
        popup: factory(m.Popup),
        polygon: factory(m.Polygon),
        polyline: factory(m.Polyline),
        circle: factory(m.Circle),
        rectangle: factory(m.Rectangle),
        latLngBounds: factory(m.LatLngBounds),
        control,
        Map: m.Map,
        Marker: m.Marker,
        TileLayer: m.TileLayer,
        Icon: m.Icon,
        DivIcon: m.DivIcon,
        Popup: m.Popup,
        Polygon: m.Polygon,
        Polyline: m.Polyline,
        Circle: m.Circle,
        Rectangle: m.Rectangle,
    };
}

/**
 * Builds an adapter for the given `leaflet` module. Version detection
 * happens once; the returned adapter has no per-call branching cost.
 */
export function createAdapter(mod: LeafletModule): LeafletAdapter {
    const version = detectVersion(mod);

    if (version === 1) {
        return createV1Adapter(mod);
    }

    return createV2Adapter(mod);
}

function createV1Adapter(mod: LeafletModule): LeafletAdapter {
    const m = mod as any;

    return {
        createMap: (container, options) => m.map(container, options),
        createTileLayer: (url, options) => m.tileLayer(url, options),
        createMarker: (latlng, options) => m.marker(latlng, options),
        createIcon: (options) => m.icon(options),
        createDivIcon: (options) => m.divIcon(options),
        createPopup: (options) => m.popup(options),
        createPolygon: (latlngs, options) => m.polygon(latlngs, options),
        createPolyline: (latlngs, options) => m.polyline(latlngs, options),
        createCircle: (latlng, options) => m.circle(latlng, options),
        createRectangle: (bounds, options) => m.rectangle(bounds, options),
        createAttributionControl: (options) => m.control.attribution(options),
        createZoomControl: (options) => m.control.zoom(options),
        legacyNamespace: m as LeafletV1Namespace,
    };
}

function createV2Adapter(mod: LeafletModule): LeafletAdapter {
    const m = mod as any;
    const legacyNamespace = buildLegacyNamespace(mod);

    return {
        createMap: (container, options) => new m.Map(container, options),
        createTileLayer: (url, options) => new m.TileLayer(url, options),
        createMarker: (latlng, options) => new m.Marker(latlng, options),
        createIcon: (options) => new m.Icon(options),
        createDivIcon: (options) => new m.DivIcon(options),
        createPopup: (options) => new m.Popup(options),
        createPolygon: (latlngs, options) => new m.Polygon(latlngs, options),
        createPolyline: (latlngs, options) => new m.Polyline(latlngs, options),
        createCircle: (latlng, options) => new m.Circle(latlng, options),
        createRectangle: (bounds, options) => new m.Rectangle(bounds, options),
        createAttributionControl: (options) => new m.Control.Attribution(options),
        createZoomControl: (options) => new m.Control.Zoom(options),
        legacyNamespace,
    };
}
