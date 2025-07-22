import { createApp } from "vue";
import { Controller } from "@hotwired/stimulus";

//#region src/render_controller.ts
var render_controller_default = class extends Controller {
	props;
	app;
	static values = {
		component: String,
		props: Object
	};
	connect() {
		this.props = this.propsValue ?? null;
		this.dispatchEvent("connect", {
			componentName: this.componentValue,
			props: this.props
		});
		const component = window.resolveVueComponent(this.componentValue);
		this.app = createApp(component, this.props);
		if (this.element.__vue_app__ !== void 0) this.element.__vue_app__.unmount();
		this.dispatchEvent("before-mount", {
			componentName: this.componentValue,
			component,
			props: this.props,
			app: this.app
		});
		this.app.mount(this.element);
		this.dispatchEvent("mount", {
			componentName: this.componentValue,
			component,
			props: this.props
		});
	}
	disconnect() {
		this.app.unmount();
		this.dispatchEvent("unmount", {
			componentName: this.componentValue,
			props: this.props
		});
	}
	dispatchEvent(name, payload) {
		this.dispatch(name, {
			detail: payload,
			prefix: "vue"
		});
	}
};

//#endregion
export { render_controller_default as default };