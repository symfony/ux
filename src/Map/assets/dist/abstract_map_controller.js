import { Controller } from "@hotwired/stimulus";
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
		extra: Object,
		icons: Array
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
		const markerDefinitions = this.markersValue;
		this.resolveMarkerIcons(markerDefinitions);
		markerDefinitions.forEach((definition) => {
			this.createMarker({ definition });
		});
		this.polygonsValue.forEach((definition) => {
			this.createPolygon({ definition });
		});
		this.polylinesValue.forEach((definition) => {
			this.createPolyline({ definition });
		});
		this.circlesValue.forEach((definition) => {
			this.createCircle({ definition });
		});
		this.rectanglesValue.forEach((definition) => {
			this.createRectangle({ definition });
		});
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
		const markerDefinitions = this.markersValue;
		this.resolveMarkerIcons(markerDefinitions);
		this.onDrawChanged(this.markers, markerDefinitions, this.createMarker, this.doRemoveMarker);
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
	resolveMarkerIcons(definitions) {
		if (!this.hasIconsValue) return;
		const icons = this.iconsValue;
		for (const { icon } of definitions) {
			if (icon?.type !== IconTypes.UxIcon) continue;
			const reference = icon._generated_html;
			if (typeof reference === "number") icon._generated_html = icons[reference];
		}
	}
	createDrawingFactory(type, draws, factory) {
		const eventBefore = `${type}:before-create`;
		const eventAfter = `${type}:after-create`;
		return ({ definition }) => {
			this.dispatchEvent(eventBefore, { definition });
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
			remover(draws.get(id));
			draws.delete(id);
		});
		newDrawDefinitions.forEach((definition) => {
			if (!draws.has(definition["@id"])) factory({ definition });
		});
	}
};
export { IconTypes, abstract_map_controller_default as default };
