import { Controller } from "@hotwired/stimulus";
import * as L from "leaflet";

//#region ../../../../assets/dist/abstract_map_controller.js
function _typeof$1(o) {
	"@babel/helpers - typeof";
	return _typeof$1 = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function(o$1) {
		return typeof o$1;
	} : function(o$1) {
		return o$1 && "function" == typeof Symbol && o$1.constructor === Symbol && o$1 !== Symbol.prototype ? "symbol" : typeof o$1;
	}, _typeof$1(o);
}
function toPrimitive$1(t, r) {
	if ("object" != _typeof$1(t) || !t) return t;
	var e = t[Symbol.toPrimitive];
	if (void 0 !== e) {
		var i = e.call(t, r || "default");
		if ("object" != _typeof$1(i)) return i;
		throw new TypeError("@@toPrimitive must return a primitive value.");
	}
	return ("string" === r ? String : Number)(t);
}
function toPropertyKey$1(t) {
	var i = toPrimitive$1(t, "string");
	return "symbol" == _typeof$1(i) ? i : i + "";
}
function _defineProperty$1(e, r, t) {
	return (r = toPropertyKey$1(r)) in e ? Object.defineProperty(e, r, {
		value: t,
		enumerable: !0,
		configurable: !0,
		writable: !0
	}) : e[r] = t, e;
}
const IconTypes = {
	Url: "url",
	Svg: "svg",
	UxIcon: "ux-icon"
};
var _Class = class extends Controller {
	constructor(..._args) {
		super(..._args);
		_defineProperty$1(this, "centerValue", void 0);
		_defineProperty$1(this, "zoomValue", void 0);
		_defineProperty$1(this, "minZoomValue", void 0);
		_defineProperty$1(this, "maxZoomValue", void 0);
		_defineProperty$1(this, "fitBoundsToMarkersValue", void 0);
		_defineProperty$1(this, "markersValue", void 0);
		_defineProperty$1(this, "polygonsValue", void 0);
		_defineProperty$1(this, "polylinesValue", void 0);
		_defineProperty$1(this, "circlesValue", void 0);
		_defineProperty$1(this, "rectanglesValue", void 0);
		_defineProperty$1(this, "optionsValue", void 0);
		_defineProperty$1(this, "extraValue", void 0);
		_defineProperty$1(this, "hasCenterValue", void 0);
		_defineProperty$1(this, "hasZoomValue", void 0);
		_defineProperty$1(this, "hasMinZoomValue", void 0);
		_defineProperty$1(this, "hasMaxZoomValue", void 0);
		_defineProperty$1(this, "hasFitBoundsToMarkersValue", void 0);
		_defineProperty$1(this, "hasMarkersValue", void 0);
		_defineProperty$1(this, "hasPolygonsValue", void 0);
		_defineProperty$1(this, "hasPolylinesValue", void 0);
		_defineProperty$1(this, "hasCirclesValue", void 0);
		_defineProperty$1(this, "hasRectanglesValue", void 0);
		_defineProperty$1(this, "hasOptionsValue", void 0);
		_defineProperty$1(this, "hasExtraValue", void 0);
		_defineProperty$1(this, "map", void 0);
		_defineProperty$1(this, "markers", /* @__PURE__ */ new Map());
		_defineProperty$1(this, "polygons", /* @__PURE__ */ new Map());
		_defineProperty$1(this, "polylines", /* @__PURE__ */ new Map());
		_defineProperty$1(this, "circles", /* @__PURE__ */ new Map());
		_defineProperty$1(this, "rectangles", /* @__PURE__ */ new Map());
		_defineProperty$1(this, "infoWindows", []);
		_defineProperty$1(this, "isConnected", false);
		_defineProperty$1(this, "createMarker", void 0);
		_defineProperty$1(this, "createPolygon", void 0);
		_defineProperty$1(this, "createPolyline", void 0);
		_defineProperty$1(this, "createCircle", void 0);
		_defineProperty$1(this, "createRectangle", void 0);
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
		if (this.fitBoundsToMarkersValue) this.doFitBoundsToMarkers();
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
	createInfoWindow({ definition, element }) {
		this.dispatchEvent("info-window:before-create", {
			definition,
			element
		});
		const infoWindow = this.doCreateInfoWindow({
			definition,
			element
		});
		this.dispatchEvent("info-window:after-create", {
			infoWindow,
			definition,
			element
		});
		this.infoWindows.push(infoWindow);
		return infoWindow;
	}
	markersValueChanged() {
		if (!this.isConnected) return;
		this.onDrawChanged(this.markers, this.markersValue, this.createMarker, this.doRemoveMarker);
		if (this.fitBoundsToMarkersValue) this.doFitBoundsToMarkers();
	}
	polygonsValueChanged() {
		if (!this.isConnected) return;
		this.onDrawChanged(this.polygons, this.polygonsValue, this.createPolygon, this.doRemovePolygon);
	}
	polylinesValueChanged() {
		if (!this.isConnected) return;
		this.onDrawChanged(this.polylines, this.polylinesValue, this.createPolyline, this.doRemovePolyline);
	}
	circlesValueChanged() {
		if (!this.isConnected) return;
		this.onDrawChanged(this.circles, this.circlesValue, this.createCircle, this.doRemoveCircle);
	}
	rectanglesValueChanged() {
		if (!this.isConnected) return;
		this.onDrawChanged(this.rectangles, this.rectanglesValue, this.createRectangle, this.doRemoveRectangle);
	}
	createDrawingFactory(type, draws, factory) {
		const eventBefore = `${type}:before-create`;
		const eventAfter = `${type}:after-create`;
		return ({ definition }) => {
			this.dispatchEvent(eventBefore, { definition });
			if (typeof definition.rawOptions !== "undefined") console.warn(`[Symfony UX Map] The event "${eventBefore}" added a deprecated "rawOptions" property to the definition, it will be removed in a next major version, replace it with "bridgeOptions" instead.`, definition);
			const drawing = factory({ definition });
			this.dispatchEvent(eventAfter, {
				[type]: drawing,
				definition
			});
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
			if (!draws.has(definition["@id"])) factory({ definition });
		});
	}
};
_defineProperty$1(_Class, "values", {
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
});

//#endregion
//#region ../../../../../../node_modules/.pnpm/@oxc-project+runtime@0.77.2/node_modules/@oxc-project/runtime/src/helpers/esm/typeof.js
function _typeof(o) {
	"@babel/helpers - typeof";
	return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function(o$1) {
		return typeof o$1;
	} : function(o$1) {
		return o$1 && "function" == typeof Symbol && o$1.constructor === Symbol && o$1 !== Symbol.prototype ? "symbol" : typeof o$1;
	}, _typeof(o);
}

//#endregion
//#region ../../../../../../node_modules/.pnpm/@oxc-project+runtime@0.77.2/node_modules/@oxc-project/runtime/src/helpers/esm/toPrimitive.js
function toPrimitive(t, r) {
	if ("object" != _typeof(t) || !t) return t;
	var e = t[Symbol.toPrimitive];
	if (void 0 !== e) {
		var i = e.call(t, r || "default");
		if ("object" != _typeof(i)) return i;
		throw new TypeError("@@toPrimitive must return a primitive value.");
	}
	return ("string" === r ? String : Number)(t);
}

//#endregion
//#region ../../../../../../node_modules/.pnpm/@oxc-project+runtime@0.77.2/node_modules/@oxc-project/runtime/src/helpers/esm/toPropertyKey.js
function toPropertyKey(t) {
	var i = toPrimitive(t, "string");
	return "symbol" == _typeof(i) ? i : i + "";
}

//#endregion
//#region ../../../../../../node_modules/.pnpm/@oxc-project+runtime@0.77.2/node_modules/@oxc-project/runtime/src/helpers/esm/defineProperty.js
function _defineProperty(e, r, t) {
	return (r = toPropertyKey(r)) in e ? Object.defineProperty(e, r, {
		value: t,
		enumerable: !0,
		configurable: !0,
		writable: !0
	}) : e[r] = t, e;
}

//#endregion
//#region src/map_controller.ts
var map_controller_default = class extends _Class {
	constructor(..._args) {
		super(..._args);
		_defineProperty(this, "map", void 0);
	}
	connect() {
		L.Marker.prototype.options.icon = L.divIcon({
			html: "<svg xmlns=\"http://www.w3.org/2000/svg\" xml:space=\"preserve\" fill-rule=\"evenodd\" stroke-linecap=\"round\" clip-rule=\"evenodd\" viewBox=\"0 0 500 820\"><defs><linearGradient id=\"__sf_ux_map_gradient_marker_fill\" x1=\"0\" x2=\"1\" y1=\"0\" y2=\"0\" gradientTransform=\"matrix(0 -37.57 37.57 0 416.45 541)\" gradientUnits=\"userSpaceOnUse\"><stop offset=\"0\" stop-color=\"#126FC6\"/><stop offset=\"1\" stop-color=\"#4C9CD1\"/></linearGradient><linearGradient id=\"__sf_ux_map_gradient_marker_border\" x1=\"0\" x2=\"1\" y1=\"0\" y2=\"0\" gradientTransform=\"matrix(0 -19.05 19.05 0 414.48 522.49)\" gradientUnits=\"userSpaceOnUse\"><stop offset=\"0\" stop-color=\"#2E6C97\"/><stop offset=\"1\" stop-color=\"#3883B7\"/></linearGradient></defs><circle cx=\"252.31\" cy=\"266.24\" r=\"83.99\" fill=\"#fff\"/><path fill=\"url(#__sf_ux_map_gradient_marker_fill)\" stroke=\"url(#__sf_ux_map_gradient_marker_border)\" stroke-width=\"1.1\" d=\"M416.54 503.61c-6.57 0-12.04 5.7-12.04 11.87 0 2.78 1.56 6.3 2.7 8.74l9.3 17.88 9.26-17.88c1.13-2.43 2.74-5.79 2.74-8.74 0-6.18-5.38-11.87-11.96-11.87Zm0 7.16a4.69 4.69 0 1 1-.02 9.4 4.69 4.69 0 0 1 .02-9.4Z\" transform=\"translate(-7889.1 -9807.44) scale(19.54)\"/></svg>",
			iconSize: [25, 41],
			iconAnchor: [12.5, 41],
			popupAnchor: [0, -41],
			className: ""
		});
		super.connect();
	}
	centerValueChanged() {
		if (this.map && this.hasCenterValue && this.centerValue && this.hasZoomValue && this.zoomValue) this.map.setView(this.centerValue, this.zoomValue);
	}
	zoomValueChanged() {
		if (this.map && this.hasZoomValue && this.zoomValue) this.map.setZoom(this.zoomValue);
	}
	minZoomValueChanged() {
		if (this.map && this.hasMinZoomValue && this.minZoomValue) this.map.setMinZoom(this.minZoomValue);
	}
	maxZoomValueChanged() {
		if (this.map && this.hasMaxZoomValue && this.maxZoomValue) this.map.setMaxZoom(this.maxZoomValue);
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
		const map = L.map(this.element, {
			center: center === null ? void 0 : center,
			zoom: zoom === null ? void 0 : zoom,
			minZoom: minZoom === null ? void 0 : minZoom,
			maxZoom: maxZoom === null ? void 0 : maxZoom,
			attributionControl: false,
			zoomControl: false,
			...options,
			...bridgeOptions
		});
		if (options.tileLayer) L.tileLayer(options.tileLayer.url, {
			attribution: options.tileLayer.attribution,
			...options.tileLayer.options
		}).addTo(map);
		if (typeof options.attributionControlOptions !== "undefined") L.control.attribution({ ...options.attributionControlOptions }).addTo(map);
		if (typeof options.zoomControlOptions !== "undefined") L.control.zoom({ ...options.zoomControlOptions }).addTo(map);
		return map;
	}
	doCreateMarker({ definition }) {
		const { "@id": _id, position, title, infoWindow, icon, rawOptions = {}, bridgeOptions = {} } = definition;
		const marker = L.marker(position, {
			title: title || void 0,
			...rawOptions,
			...bridgeOptions,
			riseOnHover: true
		}).addTo(this.map);
		if (infoWindow) this.createInfoWindow({
			definition: infoWindow,
			element: marker
		});
		if (icon) this.doCreateIcon({
			definition: icon,
			element: marker
		});
		return marker;
	}
	doRemoveMarker(marker) {
		marker.remove();
	}
	doCreatePolygon({ definition }) {
		const { "@id": _id, points, title, infoWindow, rawOptions = {}, bridgeOptions = {} } = definition;
		const polygon = L.polygon(points, {
			...rawOptions,
			...bridgeOptions
		}).addTo(this.map);
		if (title) polygon.bindPopup(title);
		if (infoWindow) this.createInfoWindow({
			definition: infoWindow,
			element: polygon
		});
		return polygon;
	}
	doRemovePolygon(polygon) {
		polygon.remove();
	}
	doCreatePolyline({ definition }) {
		const { "@id": _id, points, title, infoWindow, rawOptions = {}, bridgeOptions = {} } = definition;
		const polyline = L.polyline(points, {
			...rawOptions,
			...bridgeOptions
		}).addTo(this.map);
		if (title) polyline.bindPopup(title);
		if (infoWindow) this.createInfoWindow({
			definition: infoWindow,
			element: polyline
		});
		return polyline;
	}
	doRemovePolyline(polyline) {
		polyline.remove();
	}
	doCreateCircle({ definition }) {
		const { "@id": _id, center, radius, title, infoWindow, rawOptions = {}, bridgeOptions = {} } = definition;
		const circle = L.circle(center, {
			radius,
			...rawOptions,
			...bridgeOptions
		}).addTo(this.map);
		if (title) circle.bindPopup(title);
		if (infoWindow) this.createInfoWindow({
			definition: infoWindow,
			element: circle
		});
		return circle;
	}
	doRemoveCircle(circle) {
		circle.remove();
	}
	doCreateRectangle({ definition }) {
		const { "@id": _id, southWest, northEast, title, infoWindow, rawOptions = {}, bridgeOptions = {} } = definition;
		const rectangle = L.rectangle([[southWest.lat, southWest.lng], [northEast.lat, northEast.lng]], {
			...rawOptions,
			...bridgeOptions
		}).addTo(this.map);
		if (title) rectangle.bindPopup(title);
		if (infoWindow) this.createInfoWindow({
			definition: infoWindow,
			element: rectangle
		});
		return rectangle;
	}
	doRemoveRectangle(rectangle) {
		rectangle.remove();
	}
	doCreateInfoWindow({ definition, element }) {
		const { headerContent, content, opened, autoClose, rawOptions = {}, bridgeOptions = {} } = definition;
		element.bindPopup([headerContent, content].filter((x) => x).join("<br>"), {
			...rawOptions,
			...bridgeOptions
		});
		if (opened) {
			if (autoClose) this.closePopups();
			element.openPopup();
		}
		const popup = element.getPopup();
		if (!popup) throw new Error("Unable to get the Popup associated with the element.");
		popup.on("click", () => {
			if (autoClose) this.closePopups({ except: popup });
		});
		return popup;
	}
	doCreateIcon({ definition, element }) {
		const { type, width, height } = definition;
		let icon;
		if (type === IconTypes.Svg) icon = L.divIcon({
			html: definition.html,
			iconSize: [width, height],
			className: ""
		});
		else if (type === IconTypes.UxIcon) icon = L.divIcon({
			html: definition._generated_html,
			iconSize: [width, height],
			className: ""
		});
		else if (type === IconTypes.Url) icon = L.icon({
			iconUrl: definition.url,
			iconSize: [width, height],
			className: ""
		});
		else throw new Error(`Unsupported icon type: ${type}.`);
		element.setIcon(icon);
	}
	doFitBoundsToMarkers() {
		if (this.markers.size === 0) return;
		const bounds = [];
		this.markers.forEach((marker) => {
			const position = marker.getLatLng();
			bounds.push([position.lat, position.lng]);
		});
		this.map.fitBounds(bounds);
	}
	closePopups(options = {}) {
		this.infoWindows.forEach((popup) => {
			if (options.except && popup === options.except) return;
			popup.close();
		});
	}
};

//#endregion
export { map_controller_default as default };