import { Controller } from "@hotwired/stimulus";

//#region src/controller.ts
/**
* @author Mathias Arlaud <mathias.arlaud@gmail.com>
*/
var controller_default = class extends Controller {
	static values = {
		hub: String,
		topics: Array
	};
	eventSources = [];
	listeners = /* @__PURE__ */ new WeakMap();
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

//#endregion
export { controller_default as default };