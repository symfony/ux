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
import { Chart } from 'chart.js';

export default class extends Controller {
    connect() {
        const payload = JSON.parse(this.element.getAttribute('data-view'));
        if (Array.isArray(payload.options) && 0 === payload.options.length) {
            payload.options = {};
        }

        this._dispatchEvent('chartjs:pre-connect', { options: payload.options });

        const chart = new Chart(this.element.getContext('2d'), payload);

        this._dispatchEvent('chartjs:connect', { chart });
    }

    _dispatchEvent(name, payload = null, canBubble = false, cancelable = false) {
        const userEvent = document.createEvent('CustomEvent');
        userEvent.initCustomEvent(name, canBubble, cancelable, payload);

        this.element.dispatchEvent(userEvent);
    }
}
