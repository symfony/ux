/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Controller } from '@hotwired/stimulus';

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
export default class extends Controller {
    static values = {
        hub: String,
        topics: Array,
    };

    eventSources: Array<EventSource> = [];

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

    _notify(content: string | undefined) {
        if (!content) return;

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

    _dispatchEvent(name: string, payload: any) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload, bubbles: true }));
    }
}
