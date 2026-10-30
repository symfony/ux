import { connectStreamSource, disconnectStreamSource } from "@hotwired/turbo";
var TurboMercureStreamSourceElement = class extends HTMLElement {
	static observedAttributes = [
		"src",
		"private",
		"connected"
	];
	es;
	closeTimeout;
	connectedCallback() {
		clearTimeout(this.closeTimeout);
		this.closeTimeout = void 0;
		if (this.es) connectStreamSource(this.es);
		else this.open();
	}
	disconnectedCallback() {
		if (this.es) disconnectStreamSource(this.es);
		this.closeTimeout = setTimeout(() => {
			this.closeTimeout = void 0;
			this.close();
		});
	}
	attributeChangedCallback(name) {
		if (!this.es) return;
		if ("connected" !== name) {
			this.close();
			if (this.isConnected) this.open();
		} else if (EventSource.OPEN === this.es.readyState && !this.hasAttribute("connected")) this.setAttribute("connected", "");
	}
	open() {
		const src = this.getAttribute("src");
		if (null === src) throw new Error("The \"src\" attribute is required on <turbo-mercure-stream-source>.");
		this.es = new EventSource(src, { withCredentials: this.hasAttribute("private") });
		connectStreamSource(this.es);
		this.es.addEventListener("open", () => this.setAttribute("connected", ""));
		this.es.addEventListener("error", () => this.removeAttribute("connected"));
	}
	close() {
		if (this.es) {
			disconnectStreamSource(this.es);
			this.es.close();
			this.es = void 0;
		}
		this.removeAttribute("connected");
	}
};
if (!customElements.get("turbo-mercure-stream-source")) customElements.define("turbo-mercure-stream-source", TurboMercureStreamSourceElement);
