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
import * as Turbo from '@hotwired/turbo';

export default class extends Controller {
    connect() {
        this.streamSource = null;
        this.disconnectSource = () => {};

        let factory = { createSource: null, disconnect: null };
        this._dispatchEvent('turbo:pre-connect', factory, true);

        if (!factory.createSource) {
            throw new Error(
                'No adapter has been configured to receive Turbo Streams. You should enable one in assets/controllers.json and configure it in config/packages/turbo.yaml.'
            );

            return;
        }

        if (factory.disconnect) {
            this.disconnectSource = factory.disconnect;
        }

        const source = factory.createSource();
        if (source) {
            this.streamSource = Turbo.connectStreamSource(source);
        }

        this._dispatchEvent('turbo:connect');
    }

    disconnect() {
        if (this.streamSource) {
            Turbo.disconnectStreamSource(this.streamSource);
        }

        if (this.disconnectSource) {
            this.disconnectSource(this.streamSource);
        }
    }

    _dispatchEvent(name, payload = null, canBubble = false, cancelable = false) {
        const userEvent = document.createEvent('CustomEvent');
        userEvent.initCustomEvent(name, canBubble, cancelable, payload);

        this.element.dispatchEvent(userEvent);
    }
}
