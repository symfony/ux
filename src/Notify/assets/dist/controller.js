import { Controller } from '@hotwired/stimulus';

class default_1 extends Controller {
    constructor() {
        super(...arguments);
        this.eventSources = [];
    }
    initialize() {
        const errorMessages = [];
        if (!this.hasHubValue)
            errorMessages.push('A "hub" value pointing to the Mercure hub must be provided.');
        if (!this.hasTopicsValue)
            errorMessages.push('A "topics" value must be provided.');
        if (errorMessages.length)
            throw new Error(errorMessages.join(' '));
        this.eventSources = this.topicsValue.map((topic) => {
            const u = new URL(this.hubValue);
            u.searchParams.append('topic', topic);
            return new EventSource(u);
        });
    }
    connect() {
        if (!('Notification' in window)) {
            console.warn('This browser does not support desktop notifications.');
            return;
        }
        this.eventSources.forEach((eventSource) => {
            eventSource.addEventListener('message', (event) => this._notify(JSON.parse(event.data).summary));
        });
        this._dispatchEvent('notify:connect', { eventSources: this.eventSources });
    }
    disconnect() {
        this.eventSources.forEach((eventSource) => {
            eventSource.removeEventListener('message', this._notify);
            eventSource.close();
        });
        this.eventSources = [];
    }
    _notify(content) {
        if (!content)
            return;
        if ('granted' === Notification.permission) {
            new Notification(content);
            return;
        }
        if ('denied' !== Notification.permission) {
            Notification.requestPermission().then((permission) => {
                if ('granted' === permission) {
                    new Notification(content);
                }
            });
        }
    }
    _dispatchEvent(name, payload) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload, bubbles: true }));
    }
}
default_1.values = {
    hub: String,
    topics: Array,
};

export { default_1 as default };
