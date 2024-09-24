import AbstractMapController from '@symfony/ux-map';
import type { Point, MarkerDefinition, PolygonDefinition } from '@symfony/ux-map';
import type { LoaderOptions } from '@googlemaps/js-api-loader';
type MapOptions = Pick<google.maps.MapOptions, 'mapId' | 'gestureHandling' | 'backgroundColor' | 'disableDoubleClickZoom' | 'zoomControl' | 'zoomControlOptions' | 'mapTypeControl' | 'mapTypeControlOptions' | 'streetViewControl' | 'streetViewControlOptions' | 'fullscreenControl' | 'fullscreenControlOptions'>;
export default class extends AbstractMapController<MapOptions, google.maps.Map, google.maps.marker.AdvancedMarkerElementOptions, google.maps.marker.AdvancedMarkerElement, google.maps.InfoWindowOptions, google.maps.InfoWindow, google.maps.PolygonOptions, google.maps.Polygon> {
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
    protected doCreatePolygon(definition: PolygonDefinition<google.maps.Polygon, google.maps.InfoWindowOptions>): google.maps.Polygon;
    protected doCreateInfoWindow({ definition, element, }: {
        definition: MarkerDefinition<google.maps.marker.AdvancedMarkerElementOptions, google.maps.InfoWindowOptions>['infoWindow'] | PolygonDefinition<google.maps.Polygon, google.maps.InfoWindowOptions>['infoWindow'];
        element: google.maps.marker.AdvancedMarkerElement | google.maps.Polygon;
    }): google.maps.InfoWindow;
    private createTextOrElement;
    private closeInfoWindowsExcept;
    protected doFitBoundsToMarkers(): void;
}
export {};
