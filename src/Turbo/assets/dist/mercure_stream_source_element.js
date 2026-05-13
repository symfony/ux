import { connectStreamSource, disconnectStreamSource } from "@hotwired/turbo";
var TurboMercureStreamSourceElement = class extends HTMLElement {
	connectedCallback() {
		const src = this.getAttribute("src");
		if (null === src) throw new Error("The \"src\" attribute is required on <turbo-mercure-stream-source>.");
		this.es = new EventSource(src, { withCredentials: this.hasAttribute("private") });
		connectStreamSource(this.es);
		this.es.addEventListener("open", () => this.setAttribute("connected", ""));
		this.es.addEventListener("error", () => this.removeAttribute("connected"));
	}
	disconnectedCallback() {
		if (this.es) {
			disconnectStreamSource(this.es);
			this.es.close();
			this.es = void 0;
		}
		this.removeAttribute("connected");
	}
	attributeChangedCallback() {
		if (this.es) {
			this.disconnectedCallback();
			this.connectedCallback();
		}
	}
};
TurboMercureStreamSourceElement.observedAttributes = ["src", "private"];
if (!customElements.get("turbo-mercure-stream-source")) customElements.define("turbo-mercure-stream-source", TurboMercureStreamSourceElement);
