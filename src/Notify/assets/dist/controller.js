import { Controller } from '@hotwired/stimulus';

class default_1 extends Controller {
    constructor() {
        super(...arguments);
        this.eventSources = [];
        this.listeners = new WeakMap();
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
            const listener = (event) => this._notify(JSON.parse(event.data).summary);
            eventSource.addEventListener('message', listener);
            this.listeners.set(eventSource, listener);
        });
        this.dispatchEvent('connect', { eventSources: this.eventSources });
    }
    disconnect() {
        this.eventSources.forEach((eventSource) => {
            const listener = this.listeners.get(eventSource);
            if (listener) {
                eventSource.removeEventListener('message', listener);
            }
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
    dispatchEvent(name, payload) {
        this.dispatch(name, { detail: payload, prefix: 'notify' });
    }
}
default_1.values = {
    hub: String,
    topics: Array,
};

export { default_1 as default };
