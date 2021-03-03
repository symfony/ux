/*
 * This file is part of the Symfony package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Controller } from 'stimulus';
import { connectStreamSource, disconnectStreamSource } from '@hotwired/turbo';

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
export default class extends Controller {
    static values = {
        topic: String,
        hub: String,
    };
    es;

    initialize() {
        const errorMessages = [];
        if (!this.hasHubValue)
            errorMessages.push(
                `The element must have a "data-turbo-stream-hub-value" attribute pointing to the Mercure hub.`
            );
        if (!this.hasTopicValue)
            errorMessages.push(`The element must have a "data-turbo-stream-topic-value" attribute.`);
        if (errorMessages.length) throw new Error(errorMessages.join(' '));

        const u = new URL(this.hubValue);
        u.searchParams.append('topic', this.topicValue);

        this.url = u.toString();
    }

    connect() {
        this.es = new EventSource(this.url);
        connectStreamSource(this.es);
    }

    disconnect() {
        this.es.close();
        disconnectStreamSource(this.es);
    }
}
