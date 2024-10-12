/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Controller } from '@hotwired/stimulus';

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
export default class extends Controller {
    static values = {
        hub: String,
        topics: Array,
    };
    declare hubValue: string;
    declare topicsValue: Array<string>;
    declare readonly hasHubValue: boolean;
    declare readonly hasTopicsValue: boolean;

    eventSources: Array<EventSource> = [];
    listeners: WeakMap<EventSource, (event: MessageEvent) => void> = new WeakMap();

    initialize() {
        const errorMessages: Array<string> = [];

        if (!this.hasHubValue) errorMessages.push('A "hub" value pointing to the Mercure hub must be provided.');
        if (!this.hasTopicsValue) errorMessages.push('A "topics" value must be provided.');

        if (errorMessages.length) throw new Error(errorMessages.join(' '));

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
            const listener = (event: MessageEvent) =>
                this._notify(JSON.parse(event.data).summary, JSON.parse(event.data).content);

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

    _notify(title: string | undefined, options: NotificationOptions | undefined) {
        if (!title) return;

        if ('granted' === Notification.permission) {
            new Notification(title, options);

            return;
        }

        if ('denied' !== Notification.permission) {
            Notification.requestPermission().then((permission) => {
                if ('granted' === permission) {
                    new Notification(title, options);
                }
            });
        }
    }

    private dispatchEvent(name: string, payload: any) {
        this.dispatch(name, { detail: payload, prefix: 'notify' });
    }
}
