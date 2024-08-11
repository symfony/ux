import { Controller } from '@hotwired/stimulus';
export type Point = {
    lat: number;
    lng: number;
};
export type MapView<Options, MarkerOptions, InfoWindowOptions> = {
    center: Point | null;
    zoom: number | null;
    fitBoundsToMarkers: boolean;
    markers: Array<MarkerDefinition<MarkerOptions, InfoWindowOptions>>;
    options: Options;
};
export type MarkerDefinition<MarkerOptions, InfoWindowOptions> = {
    position: Point;
    title: string | null;
    infoWindow?: Omit<InfoWindowDefinition<InfoWindowOptions>, 'position'>;
    rawOptions?: MarkerOptions;
    extra: Record<string, unknown>;
};
export type InfoWindowDefinition<InfoWindowOptions> = {
    headerContent: string | null;
    content: string | null;
    position: Point;
    opened: boolean;
    autoClose: boolean;
    rawOptions?: InfoWindowOptions;
    extra: Record<string, unknown>;
};
export default abstract class<MapOptions, Map, MarkerOptions, Marker, InfoWindowOptions, InfoWindow> extends Controller<HTMLElement> {
    static values: {
        providerOptions: ObjectConstructor;
        view: ObjectConstructor;
    };
    viewValue: MapView<MapOptions, MarkerOptions, InfoWindowOptions>;
    protected map: Map;
    protected markers: Array<Marker>;
    protected infoWindows: Array<InfoWindow>;
    initialize(): void;
    connect(): void;
    protected abstract doCreateMap({ center, zoom, options, }: {
        center: Point | null;
        zoom: number | null;
        options: MapOptions;
    }): Map;
    createMarker(definition: MarkerDefinition<MarkerOptions, InfoWindowOptions>): Marker;
    protected abstract doCreateMarker(definition: MarkerDefinition<MarkerOptions, InfoWindowOptions>): Marker;
    protected createInfoWindow({ definition, marker, }: {
        definition: MarkerDefinition<MarkerOptions, InfoWindowOptions>['infoWindow'];
        marker: Marker;
    }): InfoWindow;
    protected abstract doCreateInfoWindow({ definition, marker, }: {
        definition: MarkerDefinition<MarkerOptions, InfoWindowOptions>['infoWindow'];
        marker: Marker;
    }): InfoWindow;
    protected abstract doFitBoundsToMarkers(): void;
    private dispatchEvent;
}
