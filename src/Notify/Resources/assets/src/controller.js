/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Controller } from 'stimulus';

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
export default class extends Controller {
    static values = { hub: String, topics: Array };
    eventSources = [];

    connect() {
        if (!('Notification' in window) || !this.hubValue || !this.topicsValue) {
            return;
        }

        this.eventSources = this.topicsValue.map(
            (topic) => new EventSource(`${this.hubValue}?topic=${encodeURIComponent(topic)}`)
        );
        this.eventSources.forEach((eventSource) =>
            eventSource.addEventListener('message', (event) => this._notify(JSON.parse(event.data).summary))
        );

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
        if (!content) {
            return;
        }

        if ('granted' === Notification.permission) {
            new Notification(content);

            return;
        }

        if ('denied' !== Notification.permission) {
            Notification.requestPermission().then(function (permission) {
                if ('granted' === permission) {
                    new Notification(content);
                }
            });
        }
    }

    _dispatchEvent(name, payload = null, canBubble = false, cancelable = false) {
        const userEvent = document.createEvent('CustomEvent');
        userEvent.initCustomEvent(name, canBubble, cancelable, payload);

        this.element.dispatchEvent(userEvent);
    }
}
