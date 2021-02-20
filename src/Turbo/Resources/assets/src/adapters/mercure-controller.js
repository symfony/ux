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

export default class extends Controller {
    static values = {
        hub: String,
        topic: String,
    };

    connect() {
        this.element.addEventListener('turbo:pre-connect', (event) => {
            const url = new URL(this.hubValue);
            url.searchParams.append('topic', this.topicValue);

            let eventSource;

            event.detail.createSource = () => {
                eventSource = new EventSource(url);

                return eventSource;
            };

            event.detail.disconnect = (eventSource) => {
                eventSource.close();
            };
        });
    }
}
