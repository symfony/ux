import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger', 'content'];

    static values = {
        open: { type: Boolean, default: false },
        name: { type: String, default: '' },
    };

    #connected = false;

    connect() {
        this.updateState();
        this.#connected = true;
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

        if (!this.openValue) {
            return;
        }

        if (this.nameValue) {
            window.dispatchEvent(
                new CustomEvent('popover:open', {
                    detail: { name: this.nameValue, source: this.element },
                })
            );
        }

        // Skip on initial render so an `open` popover does not steal focus on page load.
        if (this.#connected) {
            this.#focusContent();
        }
    }

    handleGroupOpen(event) {
        if (!this.nameValue || !this.openValue) {
            return;
        }
        if (event.detail.name !== this.nameValue || event.detail.source === this.element) {
            return;
        }
        this.openValue = false;
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
        }
    }

    #focusContent() {
        const content = this.contentTargets[0];
        const focusable =
            content?.querySelector('[autofocus]:not([disabled])') ??
            content?.querySelector(
                'input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])'
            );
        if (!focusable) {
            return;
        }

        // Wait two frames: `visibility` is transitioned, so the content computes as
        // `visibility: hidden` (not focusable) on the first frame after opening and is
        // only `visible` from the next one.
        requestAnimationFrame(() => requestAnimationFrame(() => focusable.focus()));
    }
}
