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
		_defineProperty(this, "inputTarget", void 0);
		_defineProperty(this, "placeholderTarget", void 0);
		_defineProperty(this, "previewTarget", void 0);
		_defineProperty(this, "previewClearButtonTarget", void 0);
		_defineProperty(this, "previewFilenameTarget", void 0);
		_defineProperty(this, "previewImageTarget", void 0);
	}
	initialize() {
		this.clear = this.clear.bind(this);
		this.onInputChange = this.onInputChange.bind(this);
		this.onDragEnter = this.onDragEnter.bind(this);
		this.onDragLeave = this.onDragLeave.bind(this);
	}
	connect() {
		this.clear();
		this.previewClearButtonTarget.addEventListener("click", this.clear);
		this.inputTarget.addEventListener("change", this.onInputChange);
		this.element.addEventListener("dragenter", this.onDragEnter);
		this.element.addEventListener("dragleave", this.onDragLeave);
		this.dispatchEvent("connect");
	}
	disconnect() {
		this.previewClearButtonTarget.removeEventListener("click", this.clear);
		this.inputTarget.removeEventListener("change", this.onInputChange);
		this.element.removeEventListener("dragenter", this.onDragEnter);
		this.element.removeEventListener("dragleave", this.onDragLeave);
	}
	clear() {
		this.inputTarget.value = "";
		this.inputTarget.style.display = "block";
		this.placeholderTarget.style.display = "block";
		this.previewTarget.style.display = "none";
		this.previewImageTarget.style.display = "none";
		this.previewImageTarget.style.backgroundImage = "none";
		this.previewFilenameTarget.textContent = "";
		this.dispatchEvent("clear");
	}
	onInputChange(event) {
		const file = event.target.files[0];
		if (typeof file === "undefined") return;
		this.inputTarget.style.display = "none";
		this.placeholderTarget.style.display = "none";
		this.previewFilenameTarget.textContent = file.name;
		this.previewTarget.style.display = "flex";
		this.previewImageTarget.style.display = "none";
		if (file.type && file.type.indexOf("image") !== -1) this._populateImagePreview(file);
		this.dispatchEvent("change", file);
	}
	_populateImagePreview(file) {
		if (typeof FileReader === "undefined") return;
		const reader = new FileReader();
		reader.addEventListener("load", (event) => {
			this.previewImageTarget.style.display = "block";
			this.previewImageTarget.style.backgroundImage = `url("${event.target.result}")`;
		});
		reader.readAsDataURL(file);
	}
	onDragEnter() {
		this.inputTarget.style.display = "block";
		this.placeholderTarget.style.display = "block";
		this.previewTarget.style.display = "none";
	}
	onDragLeave(event) {
		event.preventDefault();
		if (!this.element.contains(event.relatedTarget)) {
			this.inputTarget.style.display = "none";
			this.placeholderTarget.style.display = "none";
			this.previewTarget.style.display = "block";
		}
	}
	dispatchEvent(name, payload = {}) {
		this.dispatch(name, {
			detail: payload,
			prefix: "dropzone"
		});
	}
};
_defineProperty(_Class, "targets", [
	"input",
	"placeholder",
	"preview",
	"previewClearButton",
	"previewFilename",
	"previewImage"
]);

//#endregion
export { _Class as default };