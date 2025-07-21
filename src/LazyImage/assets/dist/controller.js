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
//#region src/controller.ts
var _Class = class extends Controller {
	constructor(..._args) {
		super(..._args);
		_defineProperty(this, "srcValue", void 0);
		_defineProperty(this, "srcsetValue", void 0);
		_defineProperty(this, "hasSrcsetValue", void 0);
	}
	connect() {
		const hd = new Image();
		const element = this.element;
		const srcsetString = this._calculateSrcsetString();
		hd.addEventListener("load", () => {
			element.src = this.srcValue;
			if (srcsetString) element.srcset = srcsetString;
			this.dispatchEvent("ready", { image: hd });
		});
		hd.src = this.srcValue;
		if (srcsetString) hd.srcset = srcsetString;
		this.dispatchEvent("connect", { image: hd });
	}
	_calculateSrcsetString() {
		if (!this.hasSrcsetValue) return "";
		const sets = Object.keys(this.srcsetValue).map((size) => {
			return `${this.srcsetValue[size]} ${size}`;
		});
		return sets.join(", ").trimEnd();
	}
	dispatchEvent(name, payload) {
		this.dispatch(name, {
			detail: payload,
			prefix: "lazy-image"
		});
	}
};
_defineProperty(_Class, "values", {
	src: String,
	srcset: Object
});

//#endregion
export { _Class as default };