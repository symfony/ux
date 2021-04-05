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
    connect() {
        const hd = new Image();

        hd.addEventListener('load', () => {
            this.element.src = this.element.getAttribute('data-hd-src');
            this._dispatchEvent('lazy-image:ready', { hd });
        });

        hd.src = this.element.getAttribute('data-hd-src');

        this._dispatchEvent('lazy-image:connect', { hd });
    }

    _dispatchEvent(name, payload = null, canBubble = false, cancelable = false) {
        const userEvent = document.createEvent('CustomEvent');
        userEvent.initCustomEvent(name, canBubble, cancelable, payload);

        this.element.dispatchEvent(userEvent);
    }
}
