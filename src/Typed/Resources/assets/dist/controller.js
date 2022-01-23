import { Controller } from '@hotwired/stimulus';
import Typed from 'typed.js';

class default_1 extends Controller {
    connect() {
        const options = {
            strings: this.stringsValue || null,
            typeSpeed: this.typeSpeedValue || 30,
            smartBackspace: this.smartBackspaceValue || true,
            startDelay: this.startDelayValue || 0,
            backSpeed: this.backSpeedValue || 0,
            shuffle: this.shuffleValue || false,
            backDelay: this.backDelayValue || 700,
            fadeOut: this.fadeOutValue || false,
            fadeOutClass: this.fadeOutClassValue || 'typed-fade-out',
            fadeOutDelay: this.fadeOutDelayValue || 500,
            loop: this.loopValue || false,
            loopCount: this.loopCountValue || Infinity,
            showCursor: this.showCursorValue || true,
            cursorChar: this.cursorCharValue || '.',
            autoInsertCss: this.autoInsertCssValue || true,
            attr: this.attrValue || null,
            bindInputFocusEvents: this.bindInputFocusEventsValue || false,
            contentType: this.contentTypeValue || 'html',
        };
        this._dispatchEvent('typed:pre-connect', { options });
        const typed = new Typed(this.element, options);
        this._dispatchEvent('typed:connect', { typed });
    }
    _dispatchEvent(name, payload) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload }));
    }
}
default_1.values = {
    strings: Array,
    typeSpeed: Number,
    smartBackspace: Boolean,
    startDelay: Number,
    backSpeed: Number,
    shuffle: Boolean,
    backDelay: Number,
    fadeOut: Boolean,
    fadeOutClass: String,
    fadeOutDelay: Number,
    loop: Boolean,
    loopCount: Number,
    showCursor: Boolean,
    cursorChar: String,
    autoInsertCss: Boolean,
    attr: String,
    bindInputFocusEvents: Boolean,
    contentType: String,
};

export { default_1 as default };
