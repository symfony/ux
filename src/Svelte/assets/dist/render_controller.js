import { Controller } from "@hotwired/stimulus";
import { mount, unmount } from "svelte";
var _Class = class extends Controller {
	connect() {
		this.element.innerHTML = "";
		this.props = this.propsValue ?? void 0;
		this.intro = this.introValue ?? void 0;
		this.dispatchEvent("connect");
		const Component = window.resolveSvelteComponent(this.componentValue);
		this._destroyIfExists();
		this.app = mount(Component, {
			target: this.element,
			props: this.props,
			intro: this.intro
		});
		this.element.root = this.app;
		this.dispatchEvent("mount", { component: Component });
	}
	async disconnect() {
		await this._destroyIfExists();
		this.dispatchEvent("unmount");
	}
	async _destroyIfExists() {
		if (this.element.root !== void 0) {
			await unmount(this.element.root);
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
_Class.values = {
	component: String,
	props: Object,
	intro: Boolean
};
export { _Class as default };
