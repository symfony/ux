import { Controller } from '@hotwired/stimulus';
import { enter, leave } from 'el-transition';

export default class extends Controller {

    static targets = ['trigger', 'overlay', 'dialog', 'content'];

    async open() {
        this.dialogTarget.showModal();

        await Promise.all([enter(this.overlayTarget), enter(this.contentTarget)]);

        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', 'true');
        }
    }

    async close() {
        await Promise.all([leave(this.overlayTarget), leave(this.contentTarget)]);

        this.dialogTarget.close();

        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', 'false');
        }
    }
}
