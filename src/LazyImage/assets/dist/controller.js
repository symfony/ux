import { Controller } from '@hotwired/stimulus';

class default_1 extends Controller {
    connect() {
        const hd = new Image();
        const element = this.element;
        const srcsetString = this._calculateSrcsetString();
        hd.addEventListener('load', () => {
            element.src = this.srcValue;
            if (srcsetString) {
                element.srcset = srcsetString;
            }
            this.dispatchEvent('ready', { image: hd });
        });
        hd.src = this.srcValue;
        if (srcsetString) {
            hd.srcset = srcsetString;
        }
        this.dispatchEvent('connect', { image: hd });
    }
    _calculateSrcsetString() {
        if (!this.hasSrcsetValue) {
            return '';
        }
        const sets = Object.keys(this.srcsetValue).map((size) => {
            return `${this.srcsetValue[size]} ${size}`;
        });
        return sets.join(', ').trimEnd();
    }
    dispatchEvent(name, payload) {
        this.dispatch(name, { detail: payload, prefix: 'lazy-image' });
    }
}
default_1.values = {
    src: String,
    srcset: Object,
};

export { default_1 as default };
