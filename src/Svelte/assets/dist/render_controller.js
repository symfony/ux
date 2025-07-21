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
//#region src/render_controller.ts
var _Class = class extends Controller {
	constructor(..._args) {
		super(..._args);
		_defineProperty(this, "app", void 0);
		_defineProperty(this, "componentValue", void 0);
		_defineProperty(this, "props", void 0);
		_defineProperty(this, "intro", void 0);
		_defineProperty(this, "propsValue", void 0);
		_defineProperty(this, "introValue", void 0);
	}
	connect() {
		this.element.innerHTML = "";
		this.props = this.propsValue ?? void 0;
		this.intro = this.introValue ?? void 0;
		this.dispatchEvent("connect");
		const Component = window.resolveSvelteComponent(this.componentValue);
		this._destroyIfExists();
		this.app = new Component({
			target: this.element,
			props: this.props,
			intro: this.intro
		});
		this.element.root = this.app;
		this.dispatchEvent("mount", { component: Component });
	}
	disconnect() {
		this._destroyIfExists();
		this.dispatchEvent("unmount");
	}
	_destroyIfExists() {
		if (this.element.root !== void 0) {
			this.element.root.$destroy();
			delete this.element.root;
		}
	}
	dispatchEvent(name, payload = {}) {
		const detail = {
			componentName: this.componentValue,
			props: this.props,
			intro: this.intro,
			...payload
		};
		this.dispatch(name, {
			detail,
			prefix: "svelte"
		});
	}
};
_defineProperty(_Class, "values", {
	component: String,
	props: Object,
	intro: Boolean
});

//#endregion
export { _Class as default };