/// <reference types="google.maps" />
import AbstractMapController from '@symfony/ux-map/abstract-map-controller';
import type { Point, MarkerDefinition } from '@symfony/ux-map/abstract-map-controller';
import type { LoaderOptions } from '@googlemaps/js-api-loader';
type MapOptions = Pick<google.maps.MapOptions, 'mapId' | 'gestureHandling' | 'backgroundColor' | 'disableDoubleClickZoom' | 'zoomControl' | 'zoomControlOptions' | 'mapTypeControl' | 'mapTypeControlOptions' | 'streetViewControl' | 'streetViewControlOptions' | 'fullscreenControl' | 'fullscreenControlOptions'>;
export default class extends AbstractMapController<MapOptions, google.maps.Map, google.maps.marker.AdvancedMarkerElement, google.maps.InfoWindow> {
    static values: {
        providerOptions: ObjectConstructor;
    };
    providerOptionsValue: Pick<LoaderOptions, 'apiKey' | 'id' | 'language' | 'region' | 'nonce' | 'retries' | 'url' | 'version' | 'libraries'>;
    connect(): Promise<void>;
    protected dispatchEvent(name: string, payload?: Record<string, unknown>): void;
    protected doCreateMap({ center, zoom, options, }: {
        center: Point | null;
        zoom: number | null;
        options: MapOptions;
    }): google.maps.Map;
    protected doCreateMarker(definition: MarkerDefinition<google.maps.marker.AdvancedMarkerElementOptions, google.maps.InfoWindowOptions>): google.maps.marker.AdvancedMarkerElement;
    protected doCreateInfoWindow({ definition, marker, }: {
        definition: MarkerDefinition<google.maps.marker.AdvancedMarkerElementOptions, google.maps.InfoWindowOptions>['infoWindow'];
        marker: google.maps.marker.AdvancedMarkerElement;
    }): google.maps.InfoWindow;
    private createTextOrElement;
    private closeInfoWindowsExcept;
    protected doFitBoundsToMarkers(): void;
}
export {};
