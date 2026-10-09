import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger', 'content'];

    static values = {
        openDelay: { type: Number, default: 0 },
        closeDelay: { type: Number, default: 0 },
    };

    connect() {
        this.openTimeout = null;
        this.closeTimeout = null;
        this.#setState('closed');
    }

    disconnect() {
        this.#clearTimeouts();
    }

    show() {
        this.#clearTimeouts();
        this.openTimeout = setTimeout(() => {
            this.#setState('open');
            this.openTimeout = null;
        }, this.openDelayValue);
    }

    hide() {
        this.#clearTimeouts();
        this.closeTimeout = setTimeout(() => {
            this.#setState('closed');
            this.closeTimeout = null;
        }, this.closeDelayValue);
    }

    // State lives on the trigger and content directly (not a parent group) so nested
    // sub-menus each react only to their own state.
    #setState(state) {
        this.element.dataset.state = state;
        if (this.hasTriggerTarget) {
            this.triggerTarget.dataset.state = state;
        }
        if (this.hasContentTarget) {
            this.contentTarget.dataset.state = state;
        }
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
