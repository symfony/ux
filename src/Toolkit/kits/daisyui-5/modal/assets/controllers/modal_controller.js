import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger', 'dialog'];

    static values = {
        open: Boolean,
    };

    #wasOpen = null;

    // Escape and the `modal-backdrop` form close the dialog natively, without calling close().
    #onClose = () => {
        // Reopened before this queued event ran.
        if (!this.dialogTarget.open) {
            this.#setExpanded(false);
        }
    };

    dialogTargetConnected(dialog) {
        dialog.addEventListener('close', this.#onClose);
    }

    dialogTargetDisconnected(dialog) {
        dialog.removeEventListener('close', this.#onClose);
    }

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
        if (!this.dialogTarget.open) {
            this.dialogTarget.showModal();
        }
        this.#setExpanded(true);
    }

    close() {
        this.dialogTarget.close();
    }

    #setExpanded(expanded) {
        for (const trigger of this.triggerTargets) {
            trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        }
    }
}
