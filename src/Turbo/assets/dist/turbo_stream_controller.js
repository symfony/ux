import { Controller } from "@hotwired/stimulus";
import { connectStreamSource, disconnectStreamSource } from "@hotwired/turbo";

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
//#region src/turbo_stream_controller.ts
/**
* @author Kévin Dunglas <kevin@dunglas.fr>
*/
var _Class = class extends Controller {
	constructor(..._args) {
		super(..._args);
		_defineProperty(this, "es", void 0);
		_defineProperty(this, "url", void 0);
		_defineProperty(this, "topicValue", void 0);
		_defineProperty(this, "topicsValue", void 0);
		_defineProperty(this, "withCredentialsValue", void 0);
		_defineProperty(this, "hubValue", void 0);
		_defineProperty(this, "hasHubValue", void 0);
		_defineProperty(this, "hasTopicValue", void 0);
		_defineProperty(this, "hasTopicsValue", void 0);
	}
	initialize() {
		const errorMessages = [];
		if (!this.hasHubValue) errorMessages.push("A \"hub\" value pointing to the Mercure hub must be provided.");
		if (!this.hasTopicValue && !this.hasTopicsValue) errorMessages.push("Either \"topic\" or \"topics\" value must be provided.");
		if (errorMessages.length) throw new Error(errorMessages.join(" "));
		const u = new URL(this.hubValue);
		if (this.hasTopicValue) u.searchParams.append("topic", this.topicValue);
		else this.topicsValue.forEach((topic) => {
			u.searchParams.append("topic", topic);
		});
		this.url = u.toString();
	}
	connect() {
		if (this.url) {
			this.es = new EventSource(this.url, { withCredentials: this.withCredentialsValue });
			connectStreamSource(this.es);
		}
	}
	disconnect() {
		if (this.es) {
			this.es.close();
			disconnectStreamSource(this.es);
		}
	}
};
_defineProperty(_Class, "values", {
	topic: String,
	topics: Array,
	hub: String,
	withCredentials: Boolean
});

//#endregion
export { _Class as default };