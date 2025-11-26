import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static targets = ['trigger', 'dialog'];

    async open() {
        this.dialogTarget.showModal();

        if (this.hasTriggerTarget) {
            if (this.dialogTarget.getAnimations().length > 0) {
                this.dialogTarget.addEventListener('transitionend', () => {
                    this.triggerTarget.setAttribute('aria-expanded', 'true');
                })
            } else {
                this.triggerTarget.setAttribute('aria-expanded', 'true');
            }
        }
    }

    async close() {
        this.dialogTarget.close();

        if (this.hasTriggerTarget) {
            if (this.dialogTarget.getAnimations().length > 0) {
                this.dialogTarget.addEventListener('transitionend', () => {
                    this.triggerTarget.setAttribute('aria-expanded', 'false');
                })
            } else {
                this.triggerTarget.setAttribute('aria-expanded', 'false');
            }
        }
    }
}
