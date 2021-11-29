import { Controller } from '@hotwired/stimulus';

class controller extends Controller {
    connect() {
        const hd = new Image();
        hd.addEventListener('load', () => {
            this.element.src = this.element.getAttribute('data-hd-src');
            if (this.element.getAttribute('data-hd-srcset')) {
                this.element.srcset = this.element.getAttribute('data-hd-srcset');
            }
            this._dispatchEvent('lazy-image:ready', { hd });
        });
        hd.src = this.element.getAttribute('data-hd-src');
        if (this.element.getAttribute('data-hd-srcset')) {
            hd.srcset = this.element.getAttribute('data-hd-srcset');
        }
        this._dispatchEvent('lazy-image:connect', { hd });
    }
    _dispatchEvent(name, payload = null, canBubble = false, cancelable = false) {
        const userEvent = document.createEvent('CustomEvent');
        userEvent.initCustomEvent(name, canBubble, cancelable, payload);
        this.element.dispatchEvent(userEvent);
    }
}

export { controller as default };
