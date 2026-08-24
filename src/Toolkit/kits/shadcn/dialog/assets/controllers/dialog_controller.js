import { Controller } from '@hotwired/stimulus';

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
        this._focusInitialElement();

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
