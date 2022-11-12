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
import Chart from 'chart.js/auto';

export default class extends Controller {
    readonly viewValue: any;

    static values = {
        view: Object,
    };

    connect() {
        if (!(this.element instanceof HTMLCanvasElement)) {
            throw new Error('Invalid element');
        }

        const payload = this.viewValue;
        if (Array.isArray(payload.options) && 0 === payload.options.length) {
            payload.options = {};
        }

        this._dispatchEvent('chartjs:pre-connect', { options: payload.options });

        const canvasContext = this.element.getContext('2d');
        if (!canvasContext) {
            throw new Error('Could not getContext() from Element');
        }
        const chart = new Chart(canvasContext, payload);

        this._dispatchEvent('chartjs:connect', { chart });
    }

    _dispatchEvent(name: string, payload: any) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload }));
    }
}
