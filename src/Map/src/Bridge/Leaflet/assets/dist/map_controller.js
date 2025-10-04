// ../../../../assets/dist/abstract_map_controller.js
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

// src/map_controller.ts
import "leaflet/dist/leaflet.min.css";
import * as L from "leaflet";
var map_controller_default = class extends abstract_map_controller_default {
  connect() {
    L.Marker.prototype.options.icon = L.divIcon({
      html: '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" fill-rule="evenodd" stroke-linecap="round" clip-rule="evenodd" viewBox="0 0 500 820"><defs><linearGradient id="__sf_ux_map_gradient_marker_fill" x1="0" x2="1" y1="0" y2="0" gradientTransform="matrix(0 -37.57 37.57 0 416.45 541)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#126FC6"/><stop offset="1" stop-color="#4C9CD1"/></linearGradient><linearGradient id="__sf_ux_map_gradient_marker_border" x1="0" x2="1" y1="0" y2="0" gradientTransform="matrix(0 -19.05 19.05 0 414.48 522.49)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#2E6C97"/><stop offset="1" stop-color="#3883B7"/></linearGradient></defs><circle cx="252.31" cy="266.24" r="83.99" fill="#fff"/><path fill="url(#__sf_ux_map_gradient_marker_fill)" stroke="url(#__sf_ux_map_gradient_marker_border)" stroke-width="1.1" d="M416.54 503.61c-6.57 0-12.04 5.7-12.04 11.87 0 2.78 1.56 6.3 2.7 8.74l9.3 17.88 9.26-17.88c1.13-2.43 2.74-5.79 2.74-8.74 0-6.18-5.38-11.87-11.96-11.87Zm0 7.16a4.69 4.69 0 1 1-.02 9.4 4.69 4.69 0 0 1 .02-9.4Z" transform="translate(-7889.1 -9807.44) scale(19.54)"/></svg>',
      iconSize: [25, 41],
      iconAnchor: [12.5, 41],
      popupAnchor: [0, -41],
      className: ""
      // Adding an empty class to the icon to avoid the default Leaflet styles
    });
    super.connect();
  }
  centerValueChanged() {
    if (this.map && this.hasCenterValue && this.centerValue && this.hasZoomValue && this.zoomValue) {
      this.map.setView(this.centerValue, this.zoomValue);
    }
  }
  zoomValueChanged() {
    if (this.map && this.hasZoomValue && this.zoomValue) {
      this.map.setZoom(this.zoomValue);
    }
  }
  minZoomValueChanged() {
    if (this.map && this.hasMinZoomValue && this.minZoomValue) {
      this.map.setMinZoom(this.minZoomValue);
    }
  }
  maxZoomValueChanged() {
    if (this.map && this.hasMaxZoomValue && this.maxZoomValue) {
      this.map.setMaxZoom(this.maxZoomValue);
    }
  }
  dispatchEvent(name, payload = {}) {
    payload.L = L;
    this.dispatch(name, {
      prefix: "ux:map",
      detail: payload
    });
  }
  doCreateMap({ definition }) {
    const { center, zoom, minZoom, maxZoom, options, bridgeOptions = {} } = definition;
    const map2 = L.map(this.element, {
      center: center === null ? void 0 : center,
      zoom: zoom === null ? void 0 : zoom,
      minZoom: minZoom === null ? void 0 : minZoom,
      maxZoom: maxZoom === null ? void 0 : maxZoom,
      attributionControl: false,
      zoomControl: false,
      ...options,
      ...bridgeOptions
    });
    if (options.tileLayer) {
      L.tileLayer(options.tileLayer.url, {
        attribution: options.tileLayer.attribution,
        ...options.tileLayer.options
      }).addTo(map2);
    }
    if (typeof options.attributionControlOptions !== "undefined") {
      L.control.attribution({ ...options.attributionControlOptions }).addTo(map2);
    }
    if (typeof options.zoomControlOptions !== "undefined") {
      L.control.zoom({ ...options.zoomControlOptions }).addTo(map2);
    }
    return map2;
  }
  doCreateMarker({ definition }) {
    const { "@id": _id, position, title, infoWindow, icon: icon2, rawOptions = {}, bridgeOptions = {} } = definition;
    const marker2 = L.marker(position, {
      title: title || void 0,
      ...rawOptions,
      ...bridgeOptions,
      riseOnHover: true
    }).addTo(this.map);
    if (infoWindow) {
      this.createInfoWindow({ definition: infoWindow, element: marker2 });
    }
    if (icon2) {
      if (Object.prototype.hasOwnProperty.call(bridgeOptions, "icon")) {
        console.warn('[Symfony UX Map] Defining "bridgeOptions.icon" for a marker with a custom icon is not supported and will be ignored.');
      } else if (Object.prototype.hasOwnProperty.call(rawOptions, "icon")) {
        console.warn('[Symfony UX Map] Defining "rawOptions.icon" for a marker with a custom icon is not supported and will be ignored.');
      }
      this.doCreateIcon({ definition: icon2, element: marker2 });
    }
    return marker2;
  }
  doRemoveMarker(marker2) {
    marker2.remove();
  }
  doCreatePolygon({ definition }) {
    const { "@id": _id, points, title, infoWindow, rawOptions = {}, bridgeOptions = {} } = definition;
    const polygon2 = L.polygon(points, { ...rawOptions, ...bridgeOptions }).addTo(this.map);
    if (title) {
      polygon2.bindPopup(title);
    }
    if (infoWindow) {
      this.createInfoWindow({ definition: infoWindow, element: polygon2 });
    }
    return polygon2;
  }
  doRemovePolygon(polygon2) {
    polygon2.remove();
  }
  doCreatePolyline({ definition }) {
    const { "@id": _id, points, title, infoWindow, rawOptions = {}, bridgeOptions = {} } = definition;
    const polyline2 = L.polyline(points, { ...rawOptions, ...bridgeOptions }).addTo(this.map);
    if (title) {
      polyline2.bindPopup(title);
    }
    if (infoWindow) {
      this.createInfoWindow({ definition: infoWindow, element: polyline2 });
    }
    return polyline2;
  }
  doRemovePolyline(polyline2) {
    polyline2.remove();
  }
  doCreateCircle({ definition }) {
    const { "@id": _id, center, radius, title, infoWindow, rawOptions = {}, bridgeOptions = {} } = definition;
    const circle2 = L.circle(center, { radius, ...rawOptions, ...bridgeOptions }).addTo(this.map);
    if (title) {
      circle2.bindPopup(title);
    }
    if (infoWindow) {
      this.createInfoWindow({ definition: infoWindow, element: circle2 });
    }
    return circle2;
  }
  doRemoveCircle(circle2) {
    circle2.remove();
  }
  doCreateRectangle({ definition }) {
    const { "@id": _id, southWest, northEast, title, infoWindow, rawOptions = {}, bridgeOptions = {} } = definition;
    const rectangle2 = L.rectangle(
      [
        [southWest.lat, southWest.lng],
        [northEast.lat, northEast.lng]
      ],
      { ...rawOptions, ...bridgeOptions }
    ).addTo(this.map);
    if (title) {
      rectangle2.bindPopup(title);
    }
    if (infoWindow) {
      this.createInfoWindow({ definition: infoWindow, element: rectangle2 });
    }
    return rectangle2;
  }
  doRemoveRectangle(rectangle2) {
    rectangle2.remove();
  }
  doCreateInfoWindow({
    definition,
    element
  }) {
    const { headerContent, content, opened, autoClose, rawOptions = {}, bridgeOptions = {} } = definition;
    element.bindPopup([headerContent, content].filter((x) => x).join("<br>"), { ...rawOptions, ...bridgeOptions });
    if (opened) {
      if (autoClose) {
        this.closePopups();
      }
      setTimeout(() => element.openPopup(), 0);
    }
    const popup = element.getPopup();
    if (!popup) {
      throw new Error("Unable to get the Popup associated with the element.");
    }
    popup.on("click", () => {
      if (autoClose) {
        this.closePopups({ except: popup });
      }
    });
    return popup;
  }
  doCreateIcon({ definition, element }) {
    const { type, width, height } = definition;
    let icon2;
    if (type === IconTypes.Svg) {
      icon2 = L.divIcon({
        html: definition.html,
        iconSize: [width, height],
        className: ""
        // Adding an empty class to the icon to avoid the default Leaflet styles
      });
    } else if (type === IconTypes.UxIcon) {
      icon2 = L.divIcon({
        html: definition._generated_html,
        iconSize: [width, height],
        className: ""
        // Adding an empty class to the icon to avoid the default Leaflet styles
      });
    } else if (type === IconTypes.Url) {
      icon2 = L.icon({
        iconUrl: definition.url,
        iconSize: [width, height],
        className: ""
        // Adding an empty class to the icon to avoid the default Leaflet styles
      });
    } else {
      throw new Error(`Unsupported icon type: ${type}.`);
    }
    element.setIcon(icon2);
  }
  doFitBoundsToMarkers() {
    if (this.markers.size === 0) {
      return;
    }
    const bounds = [];
    this.markers.forEach((marker2) => {
      const position = marker2.getLatLng();
      bounds.push([position.lat, position.lng]);
    });
    this.map.fitBounds(bounds);
  }
  closePopups(options = {}) {
    this.infoWindows.forEach((popup) => {
      if (options.except && popup === options.except) {
        return;
      }
      popup.close();
    });
  }
};
export {
  map_controller_default as default
};
