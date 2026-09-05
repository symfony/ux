import { t as _defineProperty } from "./defineProperty-B6pPL0VL.js";
import { Controller } from "@hotwired/stimulus";
import { getComponent } from "@symfony/ux-live-component";
var _Class = class extends Controller {
	constructor(..._args) {
		super(..._args);
		_defineProperty(this, "component", void 0);
		_defineProperty(this, "onUploadComplete", (event) => this.apply(event));
	}
	async connect() {
		this.component = await getComponent(this.element);
		this.element.addEventListener(this.eventValue, this.onUploadComplete);
	}
	disconnect() {
		this.element.removeEventListener(this.eventValue, this.onUploadComplete);
	}
	apply(event) {
		const token = event.detail?.result?.token;
		if (!token || !this.component) return;
		this.component.action(this.actionValue, {
			property: this.propertyValue,
			token
		});
	}
};
_defineProperty(_Class, "values", {
	property: String,
	action: {
		type: String,
		default: "applyUpload"
	},
	event: {
		type: String,
		default: "symfony--ux-upload--upload:complete"
	}
});
export { _Class as default };
