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
		_defineProperty(this, "visibleLabelValue", void 0);
		_defineProperty(this, "visibleIconValue", void 0);
		_defineProperty(this, "hiddenLabelValue", void 0);
		_defineProperty(this, "hiddenIconValue", void 0);
		_defineProperty(this, "buttonClassesValue", void 0);
		_defineProperty(this, "isDisplayed", false);
		_defineProperty(this, "visibleIcon", `<svg xmlns="http://www.w3.org/2000/svg" class="toggle-password-icon" viewBox="0 0 20 20" fill="currentColor">
<path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
<path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
</svg>`);
		_defineProperty(this, "hiddenIcon", `<svg xmlns="http://www.w3.org/2000/svg" class="toggle-password-icon" viewBox="0 0 20 20" fill="currentColor">
<path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
<path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
</svg>`);
	}
	connect() {
		if (this.visibleIconValue !== "Default") this.visibleIcon = this.visibleIconValue;
		if (this.hiddenIconValue !== "Default") this.hiddenIcon = this.hiddenIconValue;
		const button = this.createButton();
		this.element.insertAdjacentElement("afterend", button);
		this.dispatchEvent("connect", {
			element: this.element,
			button
		});
	}
	/**
	* @returns {HTMLButtonElement}
	*/
	createButton() {
		const button = document.createElement("button");
		button.type = "button";
		button.classList.add(...this.buttonClassesValue);
		button.setAttribute("tabindex", "-1");
		button.addEventListener("click", this.toggle.bind(this));
		button.innerHTML = `${this.visibleIcon} ${this.visibleLabelValue}`;
		return button;
	}
	/**
	* Toggle input type between "text" or "password" and update label accordingly
	*/
	toggle(event) {
		this.isDisplayed = !this.isDisplayed;
		const toggleButtonElement = event.currentTarget;
		toggleButtonElement.innerHTML = this.isDisplayed ? `${this.hiddenIcon} ${this.hiddenLabelValue}` : `${this.visibleIcon} ${this.visibleLabelValue}`;
		this.element.setAttribute("type", this.isDisplayed ? "text" : "password");
		this.dispatchEvent(this.isDisplayed ? "show" : "hide", {
			element: this.element,
			button: toggleButtonElement
		});
	}
	dispatchEvent(name, payload) {
		this.dispatch(name, {
			detail: payload,
			prefix: "toggle-password"
		});
	}
};
_defineProperty(_Class, "values", {
	visibleLabel: {
		type: String,
		default: "Show"
	},
	visibleIcon: {
		type: String,
		default: "Default"
	},
	hiddenLabel: {
		type: String,
		default: "Hide"
	},
	hiddenIcon: {
		type: String,
		default: "Default"
	},
	buttonClasses: Array
});

//#endregion
export { _Class as default };