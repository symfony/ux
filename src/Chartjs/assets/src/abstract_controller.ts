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

export default abstract class AbstractChartController extends Controller {
    declare readonly viewValue: any;

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
        const chart = this.createChart(canvasContext, payload);

        this._dispatchEvent('chartjs:connect', { chart });
    }

    _dispatchEvent(name: string, payload: any) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload }));
    }

    /**
     * To support v3 and v4 of chart.js this help function is added, could be refactored when support for v3 is dropped
     */
    abstract createChart(canvasContext: any, payload: any): any;
}
