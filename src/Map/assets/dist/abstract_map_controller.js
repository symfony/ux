// src/abstract_map_controller.ts
import { Controller } from "@hotwired/stimulus";
var IconTypes = {
  Url: "url",
  Svg: "svg",
  UxIcon: "ux-icon"
};
var abstract_map_controller_default = class extends Controller {
  constructor() {
    super(...arguments);
    this.markers = /* @__PURE__ */ new Map();
    this.polygons = /* @__PURE__ */ new Map();
    this.polylines = /* @__PURE__ */ new Map();
    this.circles = /* @__PURE__ */ new Map();
    this.rectangles = /* @__PURE__ */ new Map();
    this.infoWindows = [];
    this.isConnected = false;
  }
  connect() {
    const extra = this.hasExtraValue ? this.extraValue : {};
    const mapDefinition = {
      center: this.hasCenterValue ? this.centerValue : null,
      zoom: this.hasZoomValue ? this.zoomValue : null,
      minZoom: this.hasMinZoomValue ? this.minZoomValue : null,
      maxZoom: this.hasMaxZoomValue ? this.maxZoomValue : null,
      options: this.optionsValue,
      extra
    };
    this.dispatchEvent("pre-connect", mapDefinition);
    this.createMarker = this.createDrawingFactory("marker", this.markers, this.doCreateMarker.bind(this));
    this.createPolygon = this.createDrawingFactory("polygon", this.polygons, this.doCreatePolygon.bind(this));
    this.createPolyline = this.createDrawingFactory("polyline", this.polylines, this.doCreatePolyline.bind(this));
    this.createCircle = this.createDrawingFactory("circle", this.circles, this.doCreateCircle.bind(this));
    this.createRectangle = this.createDrawingFactory("rectangle", this.rectangles, this.doCreateRectangle.bind(this));
    this.map = this.doCreateMap({ definition: mapDefinition });
    this.markersValue.forEach((definition) => this.createMarker({ definition }));
    this.polygonsValue.forEach((definition) => this.createPolygon({ definition }));
    this.polylinesValue.forEach((definition) => this.createPolyline({ definition }));
    this.circlesValue.forEach((definition) => this.createCircle({ definition }));
    this.rectanglesValue.forEach((definition) => this.createRectangle({ definition }));
    if (this.fitBoundsToMarkersValue) {
      this.doFitBoundsToMarkers();
    }
    this.dispatchEvent("connect", {
      map: this.map,
      markers: [...this.markers.values()],
      polygons: [...this.polygons.values()],
      polylines: [...this.polylines.values()],
      circles: [...this.circles.values()],
      rectangles: [...this.rectangles.values()],
      infoWindows: this.infoWindows,
      extra
    });
    this.isConnected = true;
  }
  //region Public API
  createInfoWindow({
    definition,
    element
  }) {
    this.dispatchEvent("info-window:before-create", { definition, element });
    const infoWindow = this.doCreateInfoWindow({ definition, element });
    this.dispatchEvent("info-window:after-create", { infoWindow, definition, element });
    this.infoWindows.push(infoWindow);
    return infoWindow;
  }
  markersValueChanged() {
    if (!this.isConnected) {
      return;
    }
    this.onDrawChanged(this.markers, this.markersValue, this.createMarker, this.doRemoveMarker);
    if (this.fitBoundsToMarkersValue) {
      this.doFitBoundsToMarkers();
    }
  }
  polygonsValueChanged() {
    if (!this.isConnected) {
      return;
    }
    this.onDrawChanged(this.polygons, this.polygonsValue, this.createPolygon, this.doRemovePolygon);
  }
  polylinesValueChanged() {
    if (!this.isConnected) {
      return;
    }
    this.onDrawChanged(this.polylines, this.polylinesValue, this.createPolyline, this.doRemovePolyline);
  }
  circlesValueChanged() {
    if (!this.isConnected) {
      return;
    }
    this.onDrawChanged(this.circles, this.circlesValue, this.createCircle, this.doRemoveCircle);
  }
  rectanglesValueChanged() {
    if (!this.isConnected) {
      return;
    }
    this.onDrawChanged(this.rectangles, this.rectanglesValue, this.createRectangle, this.doRemoveRectangle);
  }
  createDrawingFactory(type, draws, factory) {
    const eventBefore = `${type}:before-create`;
    const eventAfter = `${type}:after-create`;
    return ({ definition }) => {
      this.dispatchEvent(eventBefore, { definition });
      if (typeof definition.rawOptions !== "undefined") {
        console.warn(
          `[Symfony UX Map] The event "${eventBefore}" added a deprecated "rawOptions" property to the definition, it will be removed in a next major version, replace it with "bridgeOptions" instead.`,
          definition
        );
      }
      const drawing = factory({ definition });
      this.dispatchEvent(eventAfter, { [type]: drawing, definition });
      draws.set(definition["@id"], drawing);
      return drawing;
    };
  }
  onDrawChanged(draws, newDrawDefinitions, factory, remover) {
    const idsToRemove = new Set(draws.keys());
    newDrawDefinitions.forEach((definition) => {
      idsToRemove.delete(definition["@id"]);
    });
    idsToRemove.forEach((id) => {
      const draw = draws.get(id);
      remover(draw);
      draws.delete(id);
    });
    newDrawDefinitions.forEach((definition) => {
      if (!draws.has(definition["@id"])) {
        factory({ definition });
      }
    });
  }
  //endregion
};
abstract_map_controller_default.values = {
  providerOptions: Object,
  center: Object,
  zoom: Number,
  minZoom: Number,
  maxZoom: Number,
  fitBoundsToMarkers: Boolean,
  markers: Array,
  polygons: Array,
  polylines: Array,
  circles: Array,
  rectangles: Array,
  options: Object,
  extra: Object
};
export {
  IconTypes,
  abstract_map_controller_default as default
};
