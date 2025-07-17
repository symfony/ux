/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Controller } from '@hotwired/stimulus';
import { connectStreamSource, disconnectStreamSource } from '@hotwired/turbo';

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
export default class extends Controller {
    static values = {
        topic: String,
        topics: Array,
        hub: String,
        withCredentials: Boolean,
    };
    es: EventSource | undefined;
    url: string | undefined;

    declare readonly topicValue: string;
    declare readonly topicsValue: string[];
    declare readonly withCredentialsValue: boolean;
    declare readonly hubValue: string;
    declare readonly hasHubValue: boolean;
    declare readonly hasTopicValue: boolean;
    declare readonly hasTopicsValue: boolean;

    initialize() {
        const errorMessages: string[] = [];
        if (!this.hasHubValue) errorMessages.push('A "hub" value pointing to the Mercure hub must be provided.');
        if (!this.hasTopicValue && !this.hasTopicsValue)
            errorMessages.push('Either "topic" or "topics" value must be provided.');
        if (errorMessages.length) throw new Error(errorMessages.join(' '));

        const u = new URL(this.hubValue);
        if (this.hasTopicValue) {
            u.searchParams.append('topic', this.topicValue);
        } else {
            this.topicsValue.forEach((topic) => {
                u.searchParams.append('topic', topic);
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
}
