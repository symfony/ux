import { Controller } from "@hotwired/stimulus";
import { connectStreamSource, disconnectStreamSource } from "@hotwired/turbo";
/**
* @author Kévin Dunglas <kevin@dunglas.fr>
* @deprecated Use turbo_stream_from() or the <twig:Turbo:Stream:From> Twig component instead.
*/
var _Class = class extends Controller {
	initialize() {
		console.warn("[Symfony UX] The \"mercure-turbo-stream\" Stimulus controller is deprecated since Symfony UX 3.1 and will be removed in 4.0. Use turbo_stream_from() or the <twig:Turbo:Stream:From> Twig component instead.");
		const errorMessages = [];
		if (!this.hasHubValue) errorMessages.push("A \"hub\" value pointing to the Mercure hub must be provided.");
		if (!this.hasTopicValue && !this.hasTopicsValue) errorMessages.push("Either \"topic\" or \"topics\" value must be provided.");
		if (errorMessages.length) throw new Error(errorMessages.join(" "));
		const u = new URL(this.hubValue);
		if (this.hasTopicValue) u.searchParams.append("topic", this.topicValue);
		else this.topicsValue.forEach((topic) => {
			u.searchParams.append("topic", topic);
		});
		this.url = u.toString();
	}
	connect() {
		if (this.url) {
			this.es = new EventSource(this.url, { withCredentials: this.withCredentialsValue });
			connectStreamSource(this.es);
		}
	}
	disconnect() {
		if (this.es) {
			this.es.close();
			disconnectStreamSource(this.es);
		}
	}
};
_Class.values = {
	topic: String,
	topics: Array,
	hub: String,
	withCredentials: Boolean
};
export { _Class as default };
