// src/turbo_stream_controller.ts
import { Controller } from "@hotwired/stimulus";
import { connectStreamSource, disconnectStreamSource } from "@hotwired/turbo";
var turbo_stream_controller_default = class extends Controller {
  initialize() {
    const errorMessages = [];
    if (!this.hasHubValue) errorMessages.push('A "hub" value pointing to the Mercure hub must be provided.');
    if (!this.hasTopicValue && !this.hasTopicsValue)
      errorMessages.push('Either "topic" or "topics" value must be provided.');
    if (errorMessages.length) throw new Error(errorMessages.join(" "));
    const u = new URL(this.hubValue);
    if (this.hasTopicValue) {
      u.searchParams.append("topic", this.topicValue);
    } else {
      this.topicsValue.forEach((topic) => {
        u.searchParams.append("topic", topic);
      });
    }
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
turbo_stream_controller_default.values = {
  topic: String,
  topics: Array,
  hub: String,
  withCredentials: Boolean
};
export {
  turbo_stream_controller_default as default
};
