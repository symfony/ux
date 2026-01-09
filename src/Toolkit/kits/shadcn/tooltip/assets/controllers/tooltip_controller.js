import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger', 'content'];
    static values = {
        delayDuration: { type: Number, default: 700 },
        skipDelayDuration: { type: Number, default: 300 }
    };

    static lastShownTime = 0;

    connect() {
        this.timeout = null;
    }

    disconnect() {
        this.clearTimeout();
    }

    show() {
        this.clearTimeout();

        const now = Date.now();
        const constructor = this.constructor;

        const timeSinceLastShown = now - constructor.lastShownTime;
        const delay = timeSinceLastShown < this.delayDurationValue
            ? this.skipDelayDurationValue
            : this.delayDurationValue;

        this.timeout = setTimeout(() => {
            this.contentTarget.classList.remove('opacity-0');
            this.contentTarget.classList.add('opacity-100');
            this.contentTarget.setAttribute('data-state', 'open');
            constructor.lastShownTime = now;
        }, delay);
    }

    hide() {
        this.clearTimeout();

        if (this.hasContentTarget) {
            this.contentTarget.classList.remove('opacity-100');
            this.contentTarget.classList.add('opacity-0');
            this.contentTarget.setAttribute('data-state', 'closed');
        }
    }

    clearTimeout() {
        if (this.timeout) {
            clearTimeout(this.timeout);
            this.timeout = null;
        }
    }
}
