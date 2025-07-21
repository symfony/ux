import { Controller } from "@hotwired/stimulus";
import Typed from "typed.js";

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
		_defineProperty(this, "stringsValue", void 0);
		_defineProperty(this, "typeSpeedValue", void 0);
		_defineProperty(this, "smartBackspaceValue", void 0);
		_defineProperty(this, "startDelayValue", void 0);
		_defineProperty(this, "backSpeedValue", void 0);
		_defineProperty(this, "shuffleValue", void 0);
		_defineProperty(this, "backDelayValue", void 0);
		_defineProperty(this, "fadeOutValue", void 0);
		_defineProperty(this, "fadeOutClassValue", void 0);
		_defineProperty(this, "fadeOutDelayValue", void 0);
		_defineProperty(this, "loopValue", void 0);
		_defineProperty(this, "loopCountValue", void 0);
		_defineProperty(this, "showCursorValue", void 0);
		_defineProperty(this, "cursorCharValue", void 0);
		_defineProperty(this, "autoInsertCssValue", void 0);
		_defineProperty(this, "attrValue", void 0);
		_defineProperty(this, "bindInputFocusEventsValue", void 0);
		_defineProperty(this, "contentTypeValue", void 0);
	}
	connect() {
		const options = {
			strings: this.stringsValue,
			typeSpeed: this.typeSpeedValue,
			smartBackspace: this.smartBackspaceValue,
			startDelay: this.startDelayValue,
			backSpeed: this.backSpeedValue,
			shuffle: this.shuffleValue,
			backDelay: this.backDelayValue,
			fadeOut: this.fadeOutValue,
			fadeOutClass: this.fadeOutClassValue,
			fadeOutDelay: this.fadeOutDelayValue,
			loop: this.loopValue,
			loopCount: this.loopCountValue,
			showCursor: this.showCursorValue,
			cursorChar: this.cursorCharValue,
			autoInsertCss: this.autoInsertCssValue,
			attr: this.attrValue,
			bindInputFocusEvents: this.bindInputFocusEventsValue,
			contentType: this.contentTypeValue
		};
		this.dispatchEvent("pre-connect", { options });
		const typed = new Typed(this.element, options);
		this.dispatchEvent("connect", {
			typed,
			options
		});
	}
	dispatchEvent(name, payload) {
		this.dispatch(name, {
			detail: payload,
			prefix: "typed"
		});
	}
};
_defineProperty(_Class, "values", {
	strings: Array,
	typeSpeed: {
		type: Number,
		default: 30
	},
	smartBackspace: {
		type: Boolean,
		default: true
	},
	startDelay: Number,
	backSpeed: Number,
	shuffle: Boolean,
	backDelay: {
		type: Number,
		default: 700
	},
	fadeOut: Boolean,
	fadeOutClass: {
		type: String,
		default: "typed-fade-out"
	},
	fadeOutDelay: {
		type: Number,
		default: 500
	},
	loop: Boolean,
	loopCount: {
		type: Number,
		default: Number.POSITIVE_INFINITY
	},
	showCursor: {
		type: Boolean,
		default: true
	},
	cursorChar: {
		type: String,
		default: "."
	},
	autoInsertCss: {
		type: Boolean,
		default: true
	},
	attr: String,
	bindInputFocusEvents: Boolean,
	contentType: {
		type: String,
		default: "html"
	}
});

//#endregion
export { _Class as default };