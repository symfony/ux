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

        if (this.hasTriggerTarget) {
            if (this.dialogTarget.getAnimations().length > 0) {
                this.dialogTarget.addEventListener('transitionend', () => {
                    this.triggerTarget.setAttribute('aria-expanded', 'true');
                });
            } else {
                this.triggerTarget.setAttribute('aria-expanded', 'true');
            }
        }
    }

    close() {
        this.dialogTarget.close();

        if (this.hasTriggerTarget) {
            if (this.dialogTarget.getAnimations().length > 0) {
                this.dialogTarget.addEventListener('transitionend', () => {
                    this.triggerTarget.setAttribute('aria-expanded', 'false');
                });
            } else {
                this.triggerTarget.setAttribute('aria-expanded', 'false');
            }
        }
    }
}
