/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
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
        if (!this.hasHubValue) errorMessages.push(`A "hub" value pointing to the Mercure hub must be provided.`);
        if (!this.hasTopicValue) errorMessages.push(`A "topic" value must be provided.`);
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
