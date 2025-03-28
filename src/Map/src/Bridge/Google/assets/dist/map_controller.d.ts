import type { LoaderOptions } from '@googlemaps/js-api-loader';
import AbstractMapController from '@symfony/ux-map';
import type { Icon, InfoWindowWithoutPositionDefinition, MarkerDefinition, Point, PolygonDefinition, PolylineDefinition } from '@symfony/ux-map';
type MapOptions = Pick<google.maps.MapOptions, 'mapId' | 'gestureHandling' | 'backgroundColor' | 'disableDoubleClickZoom' | 'zoomControl' | 'zoomControlOptions' | 'mapTypeControl' | 'mapTypeControlOptions' | 'streetViewControl' | 'streetViewControlOptions' | 'fullscreenControl' | 'fullscreenControlOptions'>;
export default class extends AbstractMapController<MapOptions, google.maps.Map, google.maps.marker.AdvancedMarkerElementOptions, google.maps.marker.AdvancedMarkerElement, google.maps.InfoWindowOptions, google.maps.InfoWindow, google.maps.PolygonOptions, google.maps.Polygon, google.maps.PolylineOptions, google.maps.Polyline> {
    providerOptionsValue: Pick<LoaderOptions, 'apiKey' | 'id' | 'language' | 'region' | 'nonce' | 'retries' | 'url' | 'version' | 'libraries'>;
    map: google.maps.Map;
    parser: DOMParser;
    connect(): Promise<void>;
    centerValueChanged(): void;
    zoomValueChanged(): void;
    protected dispatchEvent(name: string, payload?: Record<string, unknown>): void;
    protected doCreateMap({ center, zoom, options, }: {
        center: Point | null;
        zoom: number | null;
        options: MapOptions;
    }): google.maps.Map;
    protected doCreateMarker({ definition, }: {
        definition: MarkerDefinition<google.maps.marker.AdvancedMarkerElementOptions, google.maps.InfoWindowOptions>;
    }): google.maps.marker.AdvancedMarkerElement;
    protected doRemoveMarker(marker: google.maps.marker.AdvancedMarkerElement): void;
    protected doCreatePolygon({ definition, }: {
        definition: PolygonDefinition<google.maps.PolygonOptions, google.maps.InfoWindowOptions>;
    }): google.maps.Polygon;
    protected doRemovePolygon(polygon: google.maps.Polygon): void;
    protected doCreatePolyline({ definition, }: {
        definition: PolylineDefinition<google.maps.PolylineOptions, google.maps.InfoWindowOptions>;
    }): google.maps.Polyline;
    protected doRemovePolyline(polyline: google.maps.Polyline): void;
    protected doCreateInfoWindow({ definition, element, }: {
        definition: InfoWindowWithoutPositionDefinition<google.maps.InfoWindowOptions>;
        element: google.maps.marker.AdvancedMarkerElement | google.maps.Polygon | google.maps.Polyline;
    }): google.maps.InfoWindow;
    protected doFitBoundsToMarkers(): void;
    private createTextOrElement;
    protected doCreateIcon({ definition, element, }: {
        definition: Icon;
        element: google.maps.marker.AdvancedMarkerElement;
    }): void;
    private closeInfoWindowsExcept;
}
export {};
