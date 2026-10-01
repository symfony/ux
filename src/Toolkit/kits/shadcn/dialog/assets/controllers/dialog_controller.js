import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger', 'dialog'];

    static values = {
        open: Boolean,
    };

    #wasOpen = null;

    connect() {
        if (this.#wasOpen ?? this.openValue) {
            this.open();
        }
    }

    disconnect() {
        // A <dialog> taken out of the DOM comes back open but no longer modal, so reopen it on reconnect.
        this.#wasOpen = this.dialogTarget.open;
        if (this.#wasOpen) {
            this.dialogTarget.close();
        }
    }

    open() {
        this.dialogTarget.showModal();
        this._focusInitialElement();

        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', 'true');
        }
    }

    closeOnClickOutside({ target }) {
        if (target === this.dialogTarget) {
            this.close();
        }
    }

    _focusInitialElement() {
        // showModal() already focuses an [autofocus] target or the first focusable element;
        // when the author did not opt into autofocus, prefer the first form field instead.
        if (this.dialogTarget.querySelector('[autofocus]')) {
            return;
        }

        const field = this.dialogTarget.querySelector(
            'input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled])'
        );
        field?.focus();
    }

    close() {
        this.dialogTarget.close();

        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', 'false');
        }
    }
}
