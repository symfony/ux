import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger', 'content'];

    static values = {
        open: { type: Boolean, default: false },
    };

    connect() {
        this.updateState();
    }

    toggle(event) {
        event?.preventDefault();
        this.openValue = !this.openValue;
    }

    close() {
        this.openValue = false;
    }

    openValueChanged() {
        this.updateState();
    }

    handleOutsideClick(event) {
        if (!this.openValue || this.element.contains(event.target)) {
            return;
        }
        this.openValue = false;
    }

    handleEscape(event) {
        if (!this.openValue || event.key !== 'Escape') {
            return;
        }
        this.openValue = false;
        this.triggerTargets[0]?.focus();
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