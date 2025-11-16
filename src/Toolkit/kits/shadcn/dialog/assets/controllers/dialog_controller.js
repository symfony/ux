import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static targets = ['trigger', 'dialog'];

    open() {
        this.dialogTarget.showModal();

        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', 'true');
        }
    }

    closeOnClickOutside({ target }) {
        if (target === this.dialogTarget) {
            this.close()
        }
    }

    close() {
        this.dialogTarget.close();

        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', 'false');
        }
    }
}
