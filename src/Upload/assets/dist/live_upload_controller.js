import { Controller } from "@hotwired/stimulus";
import { getComponent } from "@symfony/ux-live-component";
var live_upload_controller_default = class extends Controller {
	static values = {
		property: String,
		action: {
			type: String,
			default: "applyUpload"
		},
		event: {
			type: String,
			default: "symfony--ux-upload--upload:complete"
		}
	};
	component;
	onUploadComplete = (event) => this.apply(event);
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
export { live_upload_controller_default as default };
