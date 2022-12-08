import { Controller } from '@hotwired/stimulus';
import Chart from 'chart.js/auto';

class default_1 extends Controller {
    connect() {
        if (!(this.element instanceof HTMLCanvasElement)) {
            throw new Error('Invalid element');
        }
        const payload = this.viewValue;
        if (Array.isArray(payload.options) && 0 === payload.options.length) {
            payload.options = {};
        }
        this.dispatchEvent('pre-connect', { options: payload.options });
        const canvasContext = this.element.getContext('2d');
        if (!canvasContext) {
            throw new Error('Could not getContext() from Element');
        }
        const chart = new Chart(canvasContext, payload);
        this.dispatchEvent('connect', { chart });
    }
    dispatchEvent(name, payload) {
        this.dispatch(name, { detail: payload, prefix: 'chartjs' });
    }
}
default_1.values = {
    view: Object,
};

export { default_1 as default };
