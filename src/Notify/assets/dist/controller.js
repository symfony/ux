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
/**
* @author Mathias Arlaud <mathias.arlaud@gmail.com>
*/
var _Class = class extends Controller {
	constructor(..._args) {
		super(..._args);
		_defineProperty(this, "hubValue", void 0);
		_defineProperty(this, "topicsValue", void 0);
		_defineProperty(this, "hasHubValue", void 0);
		_defineProperty(this, "hasTopicsValue", void 0);
		_defineProperty(this, "eventSources", []);
		_defineProperty(this, "listeners", /* @__PURE__ */ new WeakMap());
	}
	initialize() {
		const errorMessages = [];
		if (!this.hasHubValue) errorMessages.push("A \"hub\" value pointing to the Mercure hub must be provided.");
		if (!this.hasTopicsValue) errorMessages.push("A \"topics\" value must be provided.");
		if (errorMessages.length) throw new Error(errorMessages.join(" "));
		this.eventSources = this.topicsValue.map((topic) => {
			const u = new URL(this.hubValue);
			u.searchParams.append("topic", topic);
			return new EventSource(u);
		});
	}
	connect() {
		if (!("Notification" in window)) {
			console.warn("This browser does not support desktop notifications.");
			return;
		}
		this.eventSources.forEach((eventSource) => {
			const listener = (event) => {
				const { summary, content } = JSON.parse(event.data);
				this._notify(summary, content);
			};
			eventSource.addEventListener("message", listener);
			this.listeners.set(eventSource, listener);
		});
		this.dispatchEvent("connect", { eventSources: this.eventSources });
	}
	disconnect() {
		this.eventSources.forEach((eventSource) => {
			const listener = this.listeners.get(eventSource);
			if (listener) eventSource.removeEventListener("message", listener);
			eventSource.close();
		});
		this.eventSources = [];
	}
	_notify(title, options) {
		if (!title) return;
		if ("granted" === Notification.permission) {
			new Notification(title, options);
			return;
		}
		if ("denied" !== Notification.permission) Notification.requestPermission().then((permission) => {
			if ("granted" === permission) new Notification(title, options);
		});
	}
	dispatchEvent(name, payload) {
		this.dispatch(name, {
			detail: payload,
			prefix: "notify"
		});
	}
};
_defineProperty(_Class, "values", {
	hub: String,
	topics: Array
});

//#endregion
export { _Class as default };