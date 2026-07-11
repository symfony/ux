import { Controller } from "@hotwired/stimulus";
function _typeof(o) {
	"@babel/helpers - typeof";
	return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function(o) {
		return typeof o;
	} : function(o) {
		return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o;
	}, _typeof(o);
}
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
function toPropertyKey(t) {
	var i = toPrimitive(t, "string");
	return "symbol" == _typeof(i) ? i : i + "";
}
function _defineProperty(e, r, t) {
	return (r = toPropertyKey(r)) in e ? Object.defineProperty(e, r, {
		value: t,
		enumerable: !0,
		configurable: !0,
		writable: !0
	}) : e[r] = t, e;
}
var AbstractEditorController = class extends Controller {
	constructor(..._args) {
		super(..._args);
		_defineProperty(this, "instance", void 0);
	}
	async connect() {
		this.element.dispatchEvent(new CustomEvent("ux:editor:pre-connect", {
			bubbles: true,
			detail: {
				bridgeId: this.bridgeIdValue,
				format: this.formatValue,
				config: this.configValue
			}
		}));
		this.instance = await this.createEditor(this.mountTarget, this.configValue);
		this.element.dispatchEvent(new CustomEvent("ux:editor:connect", {
			bubbles: true,
			detail: {
				bridgeId: this.bridgeIdValue,
				instance: this.instance
			}
		}));
	}
	syncInput() {
		if (this.instance === void 0) return;
		const value = this.serialize(this.instance);
		const finalize = (v) => {
			this.inputTarget.value = typeof v === "string" ? v : JSON.stringify(v);
			this.element.dispatchEvent(new CustomEvent("ux:editor:change", {
				bubbles: true,
				detail: {
					value: v,
					format: this.formatValue,
					bridgeId: this.bridgeIdValue
				}
			}));
		};
		if (value && typeof value.then === "function") value.then(finalize);
		else finalize(value);
	}
	async configValueChanged(newCfg, oldCfg) {
		if (!this.instance || oldCfg === void 0) return;
		const diff = this.diff(newCfg, oldCfg);
		if (Object.keys(diff).length === 0) return;
		const hot = this.hotReloadable();
		if (Object.keys(diff).every((k) => hot.has(k))) {
			await this.applyConfig(diff, this.instance);
			return;
		}
		await this.destroyEditor(this.instance);
		this.element.dispatchEvent(new CustomEvent("ux:editor:remount", {
			bubbles: true,
			detail: {
				reason: "non-hot-keys",
				diff
			}
		}));
		this.instance = await this.createEditor(this.mountTarget, newCfg);
	}
	async disconnect() {
		if (this.instance !== void 0) {
			await this.destroyEditor(this.instance);
			this.element.dispatchEvent(new CustomEvent("ux:editor:destroy", {
				bubbles: true,
				detail: { bridgeId: this.bridgeIdValue }
			}));
			this.instance = void 0;
		}
	}
	diff(a, b) {
		const out = {};
		const keys = new Set([...Object.keys(a), ...Object.keys(b)]);
		for (const k of keys) if (JSON.stringify(a[k]) !== JSON.stringify(b[k])) out[k] = a[k];
		return out;
	}
};
_defineProperty(AbstractEditorController, "values", {
	config: Object,
	format: String,
	bridgeId: String,
	uploadUrl: String
});
_defineProperty(AbstractEditorController, "targets", ["input", "mount"]);
export { _defineProperty as n, AbstractEditorController as t };
