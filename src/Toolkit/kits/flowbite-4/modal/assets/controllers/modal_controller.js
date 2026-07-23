import { Controller } from '@hotwired/stimulus';

/**
 * @value  open                Whether the modal is open on initial render.
 * @target trigger             The element that opens the modal and reflects its expanded state.
 * @target modal               The native `<dialog>` element that is shown and closed.
 * @action open                Opens the modal.
 * @action close               Closes the modal.
 * @action closeOnClickOutside  Closes the modal when the backdrop outside the content is clicked.
 */
export default class extends Controller {
    static targets = ['trigger', 'modal'];

    static values = {
        open: Boolean,
    };

    connect() {
        if (this.openValue) {
            this.open();
        }
    }

    open() {
        this.modalTarget.showModal();

        if (this.hasTriggerTarget) {
            if (this.modalTarget.getAnimations().length > 0) {
                this.modalTarget.addEventListener(
                    'transitionend',
                    () => {
                        this.triggerTarget.setAttribute('aria-expanded', 'true');
                        this.modalTarget.setAttribute('aria-hidden', 'false');
                    },
                    { once: true }
                );
            } else {
                this.triggerTarget.setAttribute('aria-expanded', 'true');
                this.modalTarget.setAttribute('aria-hidden', 'false');
            }
        }
    }

    closeOnClickOutside({ target }) {
        if (target === this.modalTarget) {
            this.close();
        }
    }

    close() {
        this.modalTarget.close();

        if (this.hasTriggerTarget) {
            if (this.modalTarget.getAnimations().length > 0) {
                this.modalTarget.addEventListener(
                    'transitionend',
                    () => {
                        this.triggerTarget.setAttribute('aria-expanded', 'false');
                        this.modalTarget.setAttribute('aria-hidden', 'true');
                    },
                    { once: true }
                );
            } else {
                this.triggerTarget.setAttribute('aria-expanded', 'false');
                this.modalTarget.setAttribute('aria-hidden', 'true');
            }
        }
    }
}
