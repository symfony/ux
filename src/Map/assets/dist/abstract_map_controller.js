import { Controller } from "@hotwired/stimulus";

//#region ../../../node_modules/.pnpm/@oxc-project+runtime@0.77.2/node_modules/@oxc-project/runtime/src/helpers/esm/typeof.js
function _typeof(o) {
	"@babel/helpers - typeof";
	return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function(o$1) {
		return typeof o$1;
	} : function(o$1) {
		return o$1 && "function" == typeof Symbol && o$1.constructor === Symbol && o$1 !== Symbol.prototype ? "symbol" : typeof o$1;
	}, _typeof(o);
}

//#endregion
//#region ../../../node_modules/.pnpm/@oxc-project+runtime@0.77.2/node_modules/@oxc-project/runtime/src/helpers/esm/toPrimitive.js
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
//#region ../../../node_modules/.pnpm/@oxc-project+runtime@0.77.2/node_modules/@oxc-project/runtime/src/helpers/esm/toPropertyKey.js
function toPropertyKey(t) {
	var i = toPrimitive(t, "string");
	return "symbol" == _typeof(i) ? i : i + "";
}

//#endregion
//#region ../../../node_modules/.pnpm/@oxc-project+runtime@0.77.2/node_modules/@oxc-project/runtime/src/helpers/esm/defineProperty.js
function _defineProperty(e, r, t) {
	return (r = toPropertyKey(r)) in e ? Object.defineProperty(e, r, {
		value: t,
		enumerable: !0,
		configurable: !0,
		writable: !0
	}) : e[r] = t, e;
}

//#endregion
//#region src/abstract_map_controller.ts
const IconTypes = {
	Url: "url",
	Svg: "svg",
	UxIcon: "ux-icon"
};
var _Class = class extends Controller {
	constructor(..._args) {
		super(..._args);
		_defineProperty(this, "centerValue", void 0);
		_defineProperty(this, "zoomValue", void 0);
		_defineProperty(this, "minZoomValue", void 0);
		_defineProperty(this, "maxZoomValue", void 0);
		_defineProperty(this, "fitBoundsToMarkersValue", void 0);
		_defineProperty(this, "markersValue", void 0);
		_defineProperty(this, "polygonsValue", void 0);
		_defineProperty(this, "polylinesValue", void 0);
		_defineProperty(this, "circlesValue", void 0);
		_defineProperty(this, "rectanglesValue", void 0);
		_defineProperty(this, "optionsValue", void 0);
		_defineProperty(this, "extraValue", void 0);
		_defineProperty(this, "hasCenterValue", void 0);
		_defineProperty(this, "hasZoomValue", void 0);
		_defineProperty(this, "hasMinZoomValue", void 0);
		_defineProperty(this, "hasMaxZoomValue", void 0);
		_defineProperty(this, "hasFitBoundsToMarkersValue", void 0);
		_defineProperty(this, "hasMarkersValue", void 0);
		_defineProperty(this, "hasPolygonsValue", void 0);
		_defineProperty(this, "hasPolylinesValue", void 0);
		_defineProperty(this, "hasCirclesValue", void 0);
		_defineProperty(this, "hasRectanglesValue", void 0);
		_defineProperty(this, "hasOptionsValue", void 0);
		_defineProperty(this, "hasExtraValue", void 0);
		_defineProperty(this, "map", void 0);
		_defineProperty(this, "markers", /* @__PURE__ */ new Map());
		_defineProperty(this, "polygons", /* @__PURE__ */ new Map());
		_defineProperty(this, "polylines", /* @__PURE__ */ new Map());
		_defineProperty(this, "circles", /* @__PURE__ */ new Map());
		_defineProperty(this, "rectangles", /* @__PURE__ */ new Map());
		_defineProperty(this, "infoWindows", []);
		_defineProperty(this, "isConnected", false);
		_defineProperty(this, "createMarker", void 0);
		_defineProperty(this, "createPolygon", void 0);
		_defineProperty(this, "createPolyline", void 0);
		_defineProperty(this, "createCircle", void 0);
		_defineProperty(this, "createRectangle", void 0);
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
_defineProperty(_Class, "values", {
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
export { IconTypes, _Class as default };