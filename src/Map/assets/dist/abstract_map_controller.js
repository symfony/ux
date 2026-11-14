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
	infoWindowsByElement = /* @__PURE__ */ new WeakMap();
	isConnected = false;
	initialValues = {};
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
		const markerDefinitions = this.initialValues.markers ?? this.markersValue;
		this.resolveMarkerIcons(markerDefinitions);
		markerDefinitions.forEach((definition) => {
			this.createMarker({ definition });
		});
		(this.initialValues.polygons ?? this.polygonsValue).forEach((definition) => {
			this.createPolygon({ definition });
		});
		(this.initialValues.polylines ?? this.polylinesValue).forEach((definition) => {
			this.createPolyline({ definition });
		});
		(this.initialValues.circles ?? this.circlesValue).forEach((definition) => {
			this.createCircle({ definition });
		});
		(this.initialValues.rectangles ?? this.rectanglesValue).forEach((definition) => {
			this.createRectangle({ definition });
		});
		this.initialValues = {};
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
		const elementInfoWindows = this.infoWindowsByElement.get(element);
		if (elementInfoWindows) elementInfoWindows.push(infoWindow);
		else this.infoWindowsByElement.set(element, [infoWindow]);
		return infoWindow;
	}
	markersValueChanged(value) {
		if (!this.isConnected) {
			this.initialValues.markers = value;
			return;
		}
		const markerDefinitions = value ?? this.markersValue;
		this.resolveMarkerIcons(markerDefinitions);
		this.onDrawChanged(this.markers, markerDefinitions, this.createMarker, this.doRemoveMarker);
		if (this.fitBoundsToMarkersValue) this.doFitBoundsToMarkers();
	}
	polygonsValueChanged(value) {
		if (!this.isConnected) {
			this.initialValues.polygons = value;
			return;
		}
		this.onDrawChanged(this.polygons, value ?? this.polygonsValue, this.createPolygon, this.doRemovePolygon);
	}
	polylinesValueChanged(value) {
		if (!this.isConnected) {
			this.initialValues.polylines = value;
			return;
		}
		this.onDrawChanged(this.polylines, value ?? this.polylinesValue, this.createPolyline, this.doRemovePolyline);
	}
	circlesValueChanged(value) {
		if (!this.isConnected) {
			this.initialValues.circles = value;
			return;
		}
		this.onDrawChanged(this.circles, value ?? this.circlesValue, this.createCircle, this.doRemoveCircle);
	}
	rectanglesValueChanged(value) {
		if (!this.isConnected) {
			this.initialValues.rectangles = value;
			return;
		}
		this.onDrawChanged(this.rectangles, value ?? this.rectanglesValue, this.createRectangle, this.doRemoveRectangle);
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
		const removedInfoWindows = /* @__PURE__ */ new Set();
		idsToRemove.forEach((id) => {
			const draw = draws.get(id);
			const drawInfoWindows = this.infoWindowsByElement.get(draw);
			if (drawInfoWindows) {
				drawInfoWindows.forEach((infoWindow) => removedInfoWindows.add(infoWindow));
				this.infoWindowsByElement.delete(draw);
			}
			remover(draw);
			draws.delete(id);
		});
		if (removedInfoWindows.size > 0) {
			let kept = 0;
			for (const infoWindow of this.infoWindows) if (!removedInfoWindows.has(infoWindow)) this.infoWindows[kept++] = infoWindow;
			this.infoWindows.length = kept;
		}
		newDrawDefinitions.forEach((definition) => {
			if (!draws.has(definition["@id"])) factory({ definition });
		});
	}
};
export { IconTypes, abstract_map_controller_default as default };
