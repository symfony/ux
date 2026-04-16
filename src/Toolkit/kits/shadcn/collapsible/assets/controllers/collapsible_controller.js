import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger', 'content'];

    static values = {
        open: { type: Boolean, default: false },
    };

    connect() {
        this.updateState();
    }

    toggle() {
        this.openValue = !this.openValue;
    }

    openValueChanged() {
        this.updateState();
    }

    updateState() {
        const open = this.openValue;
        const state = open ? 'open' : 'closed';

        this.element.dataset.state = state;

        for (const trigger of this.triggerTargets) {
            trigger.setAttribute('aria-expanded', String(open));
            trigger.dataset.state = state;
        }

        for (const content of this.contentTargets) {
            content.dataset.state = state;
            content.setAttribute('aria-hidden', String(!open));
            if (open) {
                content.removeAttribute('hidden');
            } else {
                content.setAttribute('hidden', '');
            }
        }
    }
}
