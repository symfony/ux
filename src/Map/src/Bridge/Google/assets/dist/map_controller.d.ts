import { LoaderOptions } from "@googlemaps/js-api-loader";
import AbstractMapController, { CircleDefinition, Icon, InfoWindowDefinition, MapDefinition, MarkerDefinition, PolygonDefinition, PolylineDefinition, RectangleDefinition } from "@symfony/ux-map";

//#region src/map_controller.d.ts
type MapOptions = Pick<google.maps.MapOptions, 'mapId' | 'gestureHandling' | 'backgroundColor' | 'disableDoubleClickZoom' | 'zoomControl' | 'zoomControlOptions' | 'mapTypeControl' | 'mapTypeControlOptions' | 'streetViewControl' | 'streetViewControlOptions' | 'fullscreenControl' | 'fullscreenControlOptions'>;
declare class export_default extends AbstractMapController<MapOptions, google.maps.MapOptions, google.maps.Map, google.maps.marker.AdvancedMarkerElementOptions, google.maps.marker.AdvancedMarkerElement, google.maps.InfoWindowOptions, google.maps.InfoWindow, google.maps.PolygonOptions, google.maps.Polygon, google.maps.PolylineOptions, google.maps.Polyline, google.maps.CircleOptions, google.maps.Circle, google.maps.RectangleOptions, google.maps.Rectangle> {
  providerOptionsValue: Pick<LoaderOptions, 'apiKey' | 'id' | 'language' | 'region' | 'nonce' | 'retries' | 'url' | 'version' | 'libraries'>;
  map: google.maps.Map;
  connect(): Promise<void>;
  centerValueChanged(): void;
  zoomValueChanged(): void;
  minZoomValueChanged(): void;
  maxZoomValueChanged(): void;
  protected dispatchEvent(name: string, payload?: Record<string, unknown>): void;
  protected doCreateMap({
    definition
  }: {
    definition: MapDefinition<MapOptions, google.maps.MapOptions>;
  }): google.maps.Map;
  protected doCreateMarker({
    definition
  }: {
    definition: MarkerDefinition<google.maps.marker.AdvancedMarkerElementOptions, google.maps.InfoWindowOptions>;
  }): google.maps.marker.AdvancedMarkerElement;
  protected doRemoveMarker(marker: google.maps.marker.AdvancedMarkerElement): void;
  protected doCreatePolygon({
    definition
  }: {
    definition: PolygonDefinition<google.maps.PolygonOptions, google.maps.InfoWindowOptions>;
  }): google.maps.Polygon;
  protected doRemovePolygon(polygon: google.maps.Polygon): void;
  protected doCreatePolyline({
    definition
  }: {
    definition: PolylineDefinition<google.maps.PolylineOptions, google.maps.InfoWindowOptions>;
  }): google.maps.Polyline;
  protected doRemovePolyline(polyline: google.maps.Polyline): void;
  protected doCreateCircle({
    definition
  }: {
    definition: CircleDefinition<google.maps.CircleOptions, google.maps.InfoWindowOptions>;
  }): google.maps.Circle;
  protected doRemoveCircle(circle: google.maps.Circle): void;
  protected doCreateRectangle({
    definition
  }: {
    definition: RectangleDefinition<google.maps.RectangleOptions, google.maps.InfoWindowOptions>;
  }): google.maps.Rectangle;
  protected doRemoveRectangle(rectangle: google.maps.Rectangle): void;
  protected doCreateInfoWindow({
    definition,
    element
  }: {
    definition: Omit<InfoWindowDefinition<google.maps.InfoWindowOptions>, 'position'>;
    element: google.maps.marker.AdvancedMarkerElement | google.maps.Polygon | google.maps.Polyline | google.maps.Circle | google.maps.Rectangle;
  }): google.maps.InfoWindow;
  protected doFitBoundsToMarkers(): void;
  private createTextOrElement;
  protected doCreateIcon({
    definition,
    element
  }: {
    definition: Icon;
    element: google.maps.marker.AdvancedMarkerElement;
  }): void;
  private closeInfoWindowsExcept;
}
//#endregion
export { export_default as default };