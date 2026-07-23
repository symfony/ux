import { Controller } from '@hotwired/stimulus';

/**
 * @value  open     Whether the collapsible is expanded on initial render.
 * @target trigger  The element that toggles the collapsible and reflects its expanded state.
 * @target content  The collapsible region that is shown or hidden.
 * @action toggle   Toggles the collapsible between its open and closed states.
 */
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
