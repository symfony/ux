import { Controller } from '@hotwired/stimulus';

class default_1 extends Controller {
    connect() {
        const hd = new Image();
        const srcsetString = this._calculateSrcsetString();
        hd.addEventListener('load', () => {
            this.element.src = this.srcValue;
            if (srcsetString) {
                this.element.srcset = srcsetString;
            }
            this._dispatchEvent('lazy-image:ready', { image: hd });
        });
        hd.src = this.srcValue;
        if (srcsetString) {
            hd.srcset = srcsetString;
        }
        this._dispatchEvent('lazy-image:connect', { image: hd });
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
    _dispatchEvent(name, payload = null, canBubble = false, cancelable = false) {
        const userEvent = document.createEvent('CustomEvent');
        userEvent.initCustomEvent(name, canBubble, cancelable, payload);
        this.element.dispatchEvent(userEvent);
    }
}
default_1.values = {
    src: String,
    srcset: Object,
};

export { default_1 as default };
