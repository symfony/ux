import { Controller } from "@hotwired/stimulus";
import SwupDebugPlugin from "@swup/debug-plugin";
import SwupFadeTheme from "@swup/fade-theme";
import SwupFormsPlugin from "@swup/forms-plugin";
import SwupSlideTheme from "@swup/slide-theme";
import Swup from "swup";

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
var _Class = class extends Controller {
	constructor(..._args) {
		super(..._args);
		_defineProperty(this, "animateHistoryBrowsingValue", void 0);
		_defineProperty(this, "hasAnimateHistoryBrowsingValue", void 0);
		_defineProperty(this, "animationSelectorValue", void 0);
		_defineProperty(this, "hasAnimationSelectorValue", void 0);
		_defineProperty(this, "cacheValue", void 0);
		_defineProperty(this, "hasCacheValue", void 0);
		_defineProperty(this, "containersValue", void 0);
		_defineProperty(this, "mainElementValue", void 0);
		_defineProperty(this, "hasMainElementValue", void 0);
		_defineProperty(this, "linkSelectorValue", void 0);
		_defineProperty(this, "hasLinkSelectorValue", void 0);
		_defineProperty(this, "themeValue", void 0);
		_defineProperty(this, "debugValue", void 0);
	}
	connect() {
		const dataContainers = this.containersValue;
		const mainElement = this.mainElementValue || dataContainers[0] || "#swup";
		const allElements = [mainElement].concat(dataContainers);
		const containersList = allElements.filter((item, index) => {
			return allElements.indexOf(item) === index;
		});
		const options = {
			containers: containersList,
			plugins: ["slide" === this.themeValue ? new SwupSlideTheme({ mainElement }) : new SwupFadeTheme({ mainElement }), new SwupFormsPlugin()]
		};
		if (this.hasMainElementValue) options.mainElement = this.mainElementValue;
		if (this.hasAnimateHistoryBrowsingValue) options.animateHistoryBrowsing = this.animateHistoryBrowsingValue;
		if (this.hasAnimationSelectorValue) options.animationSelector = this.animationSelectorValue;
		if (this.hasCacheValue) options.cache = this.cacheValue;
		if (this.hasLinkSelectorValue) options.linkSelector = this.linkSelectorValue;
		if (this.debugValue) options.plugins.push(new SwupDebugPlugin());
		this.dispatchEvent("pre-connect", { options });
		const swup = new Swup(options);
		this.dispatchEvent("connect", {
			swup,
			options
		});
	}
	dispatchEvent(name, payload) {
		this.dispatch(name, {
			detail: payload,
			prefix: "swup"
		});
	}
};
_defineProperty(_Class, "values", {
	animateHistoryBrowsing: Boolean,
	animationSelector: String,
	cache: Boolean,
	containers: Array,
	linkSelector: String,
	theme: String,
	debug: Boolean,
	mainElement: String
});

//#endregion
export { _Class as default };