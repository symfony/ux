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
import { Chart, registerables } from 'chart.js';

// ChartJs 3.x
if (registerables != undefined) {
    Chart.register(...registerables);
}

let isChartInitialized = false;

export default class extends Controller {
    declare readonly viewValue: any;

    static values = {
        view: Object,
    };

    private chart: Chart | null = null;

    connect() {
        if(Chart.getChart(this.element)) {
            // Chart is already connected
            return;
        }

        if (!isChartInitialized) {
            isChartInitialized = true;
            this.dispatchEvent('init', {
                Chart,
            });
        }

        if (!(this.element instanceof HTMLCanvasElement)) {
            throw new Error('Invalid element');
        }

        const payload = this.viewValue;
        if (Array.isArray(payload.options) && 0 === payload.options.length) {
            payload.options = {};
        }

        this.dispatchEvent('pre-connect', {
            options: payload.options,
            config: payload,
        });

        const canvasContext = this.element.getContext('2d');
        if (!canvasContext) {
            throw new Error('Could not getContext() from Element');
        }
        this.chart = new Chart(canvasContext, payload);

        this.dispatchEvent('connect', { chart: this.chart });
    }

    /**
     * If the underlying data or options change, let's update the chart!
     */
    viewValueChanged(): void {
        if (this.chart) {
            const viewValue = { data: this.viewValue.data, options: this.viewValue.options };
            if (Array.isArray(viewValue.options) && 0 === viewValue.options.length) {
                viewValue.options = {};
            }
            this.dispatchEvent('view-value-change', viewValue);
            this.chart.data = viewValue.data;
            this.chart.options = viewValue.options;

            this.chart.update();

            // Updating the chart seems to sometimes result in a chart that is
            // much smaller than the canvas size. Resizing the parent element
            // triggers a mechanism in Chart.js that fixes the size.
            const parentElement = this.element.parentElement;
            if (parentElement && this.chart.options.responsive) {
                const originalWidth = parentElement.style.width;
                parentElement.style.width = parentElement.offsetWidth + 1 + 'px';
                setTimeout(() => {
                    parentElement.style.width = originalWidth;
                }, 0);
            }
        }
    }

    private dispatchEvent(name: string, payload: any) {
        this.dispatch(name, { detail: payload, prefix: 'chartjs' });
    }
}
