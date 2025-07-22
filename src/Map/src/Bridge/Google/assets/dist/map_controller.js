import { Loader } from "@googlemaps/js-api-loader";
import { Controller } from "@hotwired/stimulus";

//#region ../../../../assets/dist/abstract_map_controller.js
const IconTypes = {
	Url: "url",
	Svg: "svg",
	UxIcon: "ux-icon"
};
var abstract_map_controller_default = class extends Controller {
	static values = {
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
	map;
	markers = /* @__PURE__ */ new Map();
	polygons = /* @__PURE__ */ new Map();
	polylines = /* @__PURE__ */ new Map();
	circles = /* @__PURE__ */ new Map();
	rectangles = /* @__PURE__ */ new Map();
	infoWindows = [];
	isConnected = false;
	createMarker;
	createPolygon;
	createPolyline;
	createCircle;
	createRectangle;
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

//#endregion
//#region src/map_controller.ts
let _google;
let _loading = false;
let _loaded = false;
let _onLoadedCallbacks = [];
const parser = new DOMParser();
var map_controller_default = class extends abstract_map_controller_default {
	async connect() {
		const onLoaded = () => super.connect();
		if (_loaded) {
			onLoaded();
			return;
		}
		if (_loading) {
			_onLoadedCallbacks.push(onLoaded);
			return;
		}
		_loading = true;
		_google = { maps: {} };
		let { libraries = [],...loaderOptions } = this.providerOptionsValue;
		const loader = new Loader(loaderOptions);
		libraries = ["core", ...libraries.filter((library) => library !== "core")];
		const librariesImplementations = await Promise.all(libraries.map((library) => loader.importLibrary(library)));
		librariesImplementations.map((libraryImplementation, index) => {
			if (typeof libraryImplementation !== "object" || libraryImplementation === null) return;
			const library = libraries[index];
			if ([
				"marker",
				"places",
				"geometry",
				"journeySharing",
				"drawing",
				"visualization"
			].includes(library)) _google.maps[library] = libraryImplementation;
			else _google.maps = {
				..._google.maps,
				...libraryImplementation
			};
		});
		_loading = false;
		_loaded = true;
		onLoaded();
		_onLoadedCallbacks.forEach((callback) => callback());
		_onLoadedCallbacks = [];
	}
	centerValueChanged() {
		if (this.map && this.hasCenterValue && this.centerValue) this.map.setCenter(this.centerValue);
	}
	zoomValueChanged() {
		if (this.map && this.hasZoomValue && this.zoomValue) this.map.setZoom(this.zoomValue);
	}
	minZoomValueChanged() {
		if (this.map && this.hasMinZoomValue && this.minZoomValue) this.map.setOptions({ minZoom: this.minZoomValue });
	}
	maxZoomValueChanged() {
		if (this.map && this.hasMaxZoomValue && this.maxZoomValue) this.map.setOptions({ maxZoom: this.maxZoomValue });
	}
	dispatchEvent(name, payload = {}) {
		payload.google = _google;
		this.dispatch(name, {
			prefix: "ux:map",
			detail: payload
		});
	}
	doCreateMap({ definition }) {
		const { center, zoom, minZoom, maxZoom, options, bridgeOptions = {} } = definition;
		options.zoomControl = typeof options.zoomControlOptions !== "undefined";
		options.mapTypeControl = typeof options.mapTypeControlOptions !== "undefined";
		options.streetViewControl = typeof options.streetViewControlOptions !== "undefined";
		options.fullscreenControl = typeof options.fullscreenControlOptions !== "undefined";
		return new _google.maps.Map(this.element, {
			center,
			zoom,
			minZoom,
			maxZoom,
			...options,
			...bridgeOptions
		});
	}
	doCreateMarker({ definition }) {
		const { "@id": _id, position, title, infoWindow, icon, rawOptions = {}, bridgeOptions = {} } = definition;
		const marker = new _google.maps.marker.AdvancedMarkerElement({
			position,
			title,
			map: this.map,
			...rawOptions,
			...bridgeOptions
		});
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
		marker.map = null;
	}
	doCreatePolygon({ definition }) {
		const { "@id": _id, points, title, infoWindow, rawOptions = {}, bridgeOptions = {} } = definition;
		const polygon = new _google.maps.Polygon({
			paths: points,
			map: this.map,
			...rawOptions,
			...bridgeOptions
		});
		if (title) polygon.set("title", title);
		if (infoWindow) this.createInfoWindow({
			definition: infoWindow,
			element: polygon
		});
		return polygon;
	}
	doRemovePolygon(polygon) {
		polygon.setMap(null);
	}
	doCreatePolyline({ definition }) {
		const { "@id": _id, points, title, infoWindow, rawOptions = {}, bridgeOptions = {} } = definition;
		const polyline = new _google.maps.Polyline({
			path: points,
			map: this.map,
			...rawOptions,
			...bridgeOptions
		});
		if (title) polyline.set("title", title);
		if (infoWindow) this.createInfoWindow({
			definition: infoWindow,
			element: polyline
		});
		return polyline;
	}
	doRemovePolyline(polyline) {
		polyline.setMap(null);
	}
	doCreateCircle({ definition }) {
		const { "@id": _id, center, radius, title, infoWindow, rawOptions = {}, bridgeOptions = {} } = definition;
		const circle = new _google.maps.Circle({
			center,
			radius,
			map: this.map,
			...rawOptions,
			...bridgeOptions
		});
		if (title) circle.set("title", title);
		if (infoWindow) this.createInfoWindow({
			definition: infoWindow,
			element: circle
		});
		return circle;
	}
	doRemoveCircle(circle) {
		circle.setMap(null);
	}
	doCreateRectangle({ definition }) {
		const { northEast, southWest, title, infoWindow, rawOptions = {}, bridgeOptions = {} } = definition;
		const rectangle = new _google.maps.Rectangle({
			bounds: new _google.maps.LatLngBounds(southWest, northEast),
			map: this.map,
			...rawOptions,
			...bridgeOptions
		});
		if (title) rectangle.set("title", title);
		if (infoWindow) this.createInfoWindow({
			definition: infoWindow,
			element: rectangle
		});
		return rectangle;
	}
	doRemoveRectangle(rectangle) {
		rectangle.setMap(null);
	}
	doCreateInfoWindow({ definition, element }) {
		const { headerContent, content, opened, autoClose, rawOptions = {}, bridgeOptions = {} } = definition;
		let position = null;
		if (element instanceof google.maps.Circle) position = element.getCenter();
		else if (element instanceof google.maps.Rectangle) position = element.getBounds()?.getCenter() || null;
		else if (element instanceof google.maps.Polygon || element instanceof google.maps.Polyline) {}
		const infoWindowOptions = {
			headerContent: this.createTextOrElement(headerContent),
			content: this.createTextOrElement(content),
			position,
			...rawOptions,
			...bridgeOptions
		};
		const infoWindow = new _google.maps.InfoWindow(infoWindowOptions);
		element.addListener("click", (event) => {
			if (autoClose) this.closeInfoWindowsExcept(infoWindow);
			if (infoWindowOptions.position === null) infoWindow.setPosition(event.latLng);
			infoWindow.open({
				map: this.map,
				anchor: element
			});
		});
		if (opened) {
			if (autoClose) this.closeInfoWindowsExcept(infoWindow);
			infoWindow.open({
				map: this.map,
				anchor: element
			});
		}
		return infoWindow;
	}
	doFitBoundsToMarkers() {
		if (this.markers.size === 0) return;
		const bounds = new google.maps.LatLngBounds();
		this.markers.forEach((marker) => {
			if (!marker.position) return;
			bounds.extend(marker.position);
		});
		this.map.fitBounds(bounds);
	}
	createTextOrElement(content) {
		if (!content) return null;
		if (content.includes("<")) {
			const div = document.createElement("div");
			div.innerHTML = content;
			return div;
		}
		return content;
	}
	doCreateIcon({ definition, element }) {
		const { type, width, height } = definition;
		if (type === IconTypes.Svg) element.content = parser.parseFromString(definition.html, "image/svg+xml").documentElement;
		else if (type === IconTypes.UxIcon) element.content = parser.parseFromString(definition._generated_html, "image/svg+xml").documentElement;
		else if (type === IconTypes.Url) {
			const icon = document.createElement("img");
			icon.width = width;
			icon.height = height;
			icon.src = definition.url;
			element.content = icon;
		} else throw new Error(`Unsupported icon type: ${type}.`);
	}
	closeInfoWindowsExcept(infoWindow) {
		this.infoWindows.forEach((otherInfoWindow) => {
			if (otherInfoWindow !== infoWindow) otherInfoWindow.close();
		});
	}
};

//#endregion
export { map_controller_default as default };