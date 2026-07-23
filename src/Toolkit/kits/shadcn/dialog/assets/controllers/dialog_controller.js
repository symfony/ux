import { Controller } from '@hotwired/stimulus';

/**
 * @value  open     Whether the dialog is open on initial render.
 * @target trigger  The element that opens the dialog and reflects its expanded state.
 * @target dialog   The native `<dialog>` element that is shown and closed.
 * @action open     Opens the dialog.
 * @action close    Closes the dialog.
 * @action closeOnClickOutside  Closes the dialog when the backdrop outside the content is clicked.
 */
export default class extends Controller {
    static targets = ['trigger', 'dialog'];

    static values = {
        open: Boolean,
    };

    connect() {
        if (this.openValue) {
            this.open();
        }
    }

    open() {
        this.dialogTarget.showModal();

        if (this.hasTriggerTarget) {
            if (this.dialogTarget.getAnimations().length > 0) {
                this.dialogTarget.addEventListener(
                    'transitionend',
                    () => {
                        this.triggerTarget.setAttribute('aria-expanded', 'true');
                    },
                    { once: true }
                );
            } else {
                this.triggerTarget.setAttribute('aria-expanded', 'true');
            }
        }
    }

    closeOnClickOutside({ target }) {
        if (target === this.dialogTarget) {
            this.close();
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
