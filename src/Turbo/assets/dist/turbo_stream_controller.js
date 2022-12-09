import { Controller } from '@hotwired/stimulus';
import { connectStreamSource, disconnectStreamSource } from '@hotwired/turbo';

class default_1 extends Controller {
    initialize() {
        const errorMessages = [];
        if (!this.hasHubValue)
            errorMessages.push('A "hub" value pointing to the Mercure hub must be provided.');
        if (!this.hasTopicValue)
            errorMessages.push('A "topic" value must be provided.');
        if (errorMessages.length)
            throw new Error(errorMessages.join(' '));
        const u = new URL(this.hubValue);
        u.searchParams.append('topic', this.topicValue);
        this.url = u.toString();
    }
    connect() {
        if (this.url) {
            this.es = new EventSource(this.url);
            connectStreamSource(this.es);
        }
    }
    disconnect() {
        if (this.es) {
            this.es.close();
            disconnectStreamSource(this.es);
        }
    }
}
default_1.values = {
    topic: String,
    hub: String,
};

export { default_1 as default };
