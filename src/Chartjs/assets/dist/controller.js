import { Controller } from "@hotwired/stimulus";
import { Chart, registerables } from "chart.js";

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
//#region src/controller.ts
if (registerables) Chart.register(...registerables);
let isChartInitialized = false;
var _Class = class extends Controller {
	constructor(..._args) {
		super(..._args);
		_defineProperty(this, "viewValue", void 0);
		_defineProperty(this, "chart", null);
	}
	connect() {
		if (!isChartInitialized) {
			isChartInitialized = true;
			this.dispatchEvent("init", { Chart });
		}
		if (!(this.element instanceof HTMLCanvasElement)) throw new Error("Invalid element");
		const payload = this.viewValue;
		if (Array.isArray(payload.options) && 0 === payload.options.length) payload.options = {};
		this.dispatchEvent("pre-connect", {
			options: payload.options,
			config: payload
		});
		const canvasContext = this.element.getContext("2d");
		if (!canvasContext) throw new Error("Could not getContext() from Element");
		this.chart = new Chart(canvasContext, payload);
		this.dispatchEvent("connect", { chart: this.chart });
	}
	disconnect() {
		this.dispatchEvent("disconnect", { chart: this.chart });
		if (this.chart) {
			this.chart.destroy();
			this.chart = null;
		}
	}
	/**
	* If the underlying data or options change, let's update the chart!
	*/
	viewValueChanged() {
		if (this.chart) {
			const viewValue = {
				data: this.viewValue.data,
				options: this.viewValue.options
			};
			if (Array.isArray(viewValue.options) && 0 === viewValue.options.length) viewValue.options = {};
			this.dispatchEvent("view-value-change", viewValue);
			this.chart.data = viewValue.data;
			this.chart.options = viewValue.options;
			this.chart.update();
			const parentElement = this.element.parentElement;
			if (parentElement && this.chart.options.responsive) {
				const originalWidth = parentElement.style.width;
				parentElement.style.width = `${parentElement.offsetWidth + 1}px`;
				setTimeout(() => {
					parentElement.style.width = originalWidth;
				}, 0);
			}
		}
	}
	dispatchEvent(name, payload) {
		this.dispatch(name, {
			detail: payload,
			prefix: "chartjs"
		});
	}
};
_defineProperty(_Class, "values", { view: Object });

//#endregion
export { _Class as default };