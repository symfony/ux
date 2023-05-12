import { Controller } from '@hotwired/stimulus';
import Chart from 'chart.js/auto';

let isChartInitialized = false;
class default_1 extends Controller {
    constructor() {
        super(...arguments);
        this.chart = null;
    }
    connect() {
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
    viewValueChanged() {
        if (this.chart) {
            this.chart.data = this.viewValue.data;
            this.chart.options = this.viewValue.options;
            this.chart.update();
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
    dispatchEvent(name, payload) {
        this.dispatch(name, { detail: payload, prefix: 'chartjs' });
    }
}
default_1.values = {
    view: Object,
};

export { default_1 as default };
