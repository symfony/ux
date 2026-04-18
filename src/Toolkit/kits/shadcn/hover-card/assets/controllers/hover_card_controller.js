import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        openDelay: { type: Number, default: 0 },
        closeDelay: { type: Number, default: 0 },
    };

    connect() {
        this.openTimeout = null;
        this.closeTimeout = null;
        this.element.dataset.state = 'closed';
    }

    disconnect() {
        this.#clearTimeouts();
    }

    show() {
        this.#clearTimeouts();
        this.openTimeout = setTimeout(() => {
            this.element.dataset.state = 'open';
            this.openTimeout = null;
        }, this.openDelayValue);
    }

    hide() {
        this.#clearTimeouts();
        this.closeTimeout = setTimeout(() => {
            this.element.dataset.state = 'closed';
            this.closeTimeout = null;
        }, this.closeDelayValue);
    }

    #clearTimeouts() {
        if (this.openTimeout) {
            clearTimeout(this.openTimeout);
            this.openTimeout = null;
        }
        if (this.closeTimeout) {
            clearTimeout(this.closeTimeout);
            this.closeTimeout = null;
        }
    }
}
