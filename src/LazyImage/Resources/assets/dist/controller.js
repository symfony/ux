import { Controller } from '@hotwired/stimulus';

class default_1 extends Controller {
    connect() {
        const hd = new Image();
        const srcsetString = this._calculateSrcsetString();
        hd.addEventListener('load', () => {
            this.element.src = this.hdSrcValue;
            if (srcsetString) {
                this.element.srcset = srcsetString;
            }
            this._dispatchEvent('lazy-image:ready', { hd });
        });
        hd.src = this.hdSrcValue;
        if (srcsetString) {
            hd.srcset = srcsetString;
        }
        this._dispatchEvent('lazy-image:connect', { hd });
    }
    _calculateSrcsetString() {
        if (!this.hasHdSrcsetValue) {
            return '';
        }
        const sets = Object.keys(this.hdSrcsetValue).map((size => {
            return `${this.hdSrcsetValue[size]} ${size}`;
        }));
        return sets.join(', ').trimEnd();
    }
    _dispatchEvent(name, payload = null, canBubble = false, cancelable = false) {
        const userEvent = document.createEvent('CustomEvent');
        userEvent.initCustomEvent(name, canBubble, cancelable, payload);
        this.element.dispatchEvent(userEvent);
    }
}
default_1.values = {
    hdSrc: String,
    hdSrcset: Object
};

export { default_1 as default };
