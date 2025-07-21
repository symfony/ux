import { Controller } from "@hotwired/stimulus";
import React from "react";
import { createRoot } from "react-dom/client";

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
//#region src/render_controller.ts
var _Class = class extends Controller {
	constructor(..._args) {
		super(..._args);
		_defineProperty(this, "componentValue", void 0);
		_defineProperty(this, "propsValue", void 0);
		_defineProperty(this, "permanentValue", void 0);
	}
	connect() {
		const props = this.propsValue ? this.propsValue : null;
		this.dispatchEvent("connect", {
			component: this.componentValue,
			props
		});
		if (!this.componentValue) throw new Error("No component specified.");
		const component = window.resolveReactComponent(this.componentValue);
		this._renderReactElement(React.createElement(component, props, null));
		this.dispatchEvent("mount", {
			componentName: this.componentValue,
			component,
			props
		});
	}
	disconnect() {
		if (this.permanentValue) return;
		this.element.root.unmount();
		this.dispatchEvent("unmount", {
			component: this.componentValue,
			props: this.propsValue ? this.propsValue : null
		});
	}
	_renderReactElement(reactElement) {
		const element = this.element;
		if (!element.root) element.root = createRoot(this.element);
		element.root.render(reactElement);
	}
	dispatchEvent(name, payload) {
		this.dispatch(name, {
			detail: payload,
			prefix: "react"
		});
	}
};
_defineProperty(_Class, "values", {
	component: String,
	props: Object,
	permanent: {
		type: Boolean,
		default: false
	}
});

//#endregion
export { _Class as default };