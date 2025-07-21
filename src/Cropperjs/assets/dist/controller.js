import { Controller } from "@hotwired/stimulus";
import Cropper from "cropperjs";

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
var CropperController = class extends Controller {
	constructor(..._args) {
		super(..._args);
		_defineProperty(this, "publicUrlValue", void 0);
		_defineProperty(this, "optionsValue", void 0);
	}
	connect() {
		const img = document.createElement("img");
		img.classList.add("cropperjs-image");
		img.src = this.publicUrlValue;
		const parent = this.element.parentNode;
		if (!parent) throw new Error("Missing parent node for Cropperjs");
		parent.appendChild(img);
		const options = this.optionsValue;
		this.dispatchEvent("pre-connect", {
			options,
			img
		});
		const cropper = new Cropper(img, options);
		img.addEventListener("crop", (event) => {
			this.element.value = JSON.stringify(event.detail);
		});
		this.dispatchEvent("connect", {
			cropper,
			options,
			img
		});
	}
	dispatchEvent(name, payload) {
		this.dispatch(name, {
			detail: payload,
			prefix: "cropperjs"
		});
	}
};
_defineProperty(CropperController, "values", {
	publicUrl: String,
	options: Object
});

//#endregion
export { CropperController as default };