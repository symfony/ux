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

export default class extends Controller {
    static values = {
        src: String,
        srcset: Object,
    };

    connect() {
        const hd = new Image();

        const srcsetString = this._calculateSrcsetString();

        hd.addEventListener('load', () => {
            this.element.src = this.srcValue;
            if (srcsetString) {
                this.element.srcset = srcsetString;
            }
            this._dispatchEvent('lazy-image:ready', { hd });
        });

        hd.src = this.srcValue;
        if (srcsetString) {
            hd.srcset = srcsetString;
        }

        this._dispatchEvent('lazy-image:connect', { hd });
    }

    _calculateSrcsetString(): string {
        if (!this.hasSrcsetValue) {
            return '';
        }

        const sets = Object.keys(this.srcsetValue).map((size) => {
            return `${this.srcsetValue[size]} ${size}`;
        });

        return sets.join(', ').trimEnd();
    }

    _dispatchEvent(name, payload = null, canBubble = false, cancelable = false) {
        const userEvent = document.createEvent('CustomEvent');
        userEvent.initCustomEvent(name, canBubble, cancelable, payload);

        this.element.dispatchEvent(userEvent);
    }
}
