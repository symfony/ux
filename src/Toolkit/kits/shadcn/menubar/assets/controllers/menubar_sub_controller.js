import { Controller } from '@hotwired/stimulus';

// The native path needs both: a popover without anchor positioning would be centred by the browser.
const native = HTMLElement.prototype.hasOwnProperty('popover') && CSS.supports('position-area', 'block-end');
let anchors = 0;

export default class extends Controller {
    static targets = ['content'];

    static values = {
        openDelay: { type: Number, default: 0 },
        closeDelay: { type: Number, default: 0 },
    };

    #anchor = `--menubar-sub-${++anchors}`;

    connect() {
        this.openTimeout = null;
        this.closeTimeout = null;
        this.element.dataset.state = 'closed';
    }

    disconnect() {
        this.#clearTimeouts();
        this.element.dataset.state = 'closed';
    }

    contentTargetConnected(content) {
        if (!native) {
            return;
        }
        this.#connectNative(content);
        content.addEventListener('beforetoggle', this.#onBeforeToggle);
    }

    contentTargetDisconnected(content) {
        if (!native) {
            return;
        }
        content.removeEventListener('beforetoggle', this.#onBeforeToggle);
        content.removeAttribute('popover');
        content.style.removeProperty('position-anchor');
        this.element.style.removeProperty('anchor-name');
    }

    show() {
        this.#clearTimeouts();
        this.openTimeout = setTimeout(() => {
            this.openTimeout = null;
            // The menu can close while the open delay runs.
            if ('open' === this.element.closest('[data-slot="menubar-menu"]')?.dataset.state) {
                this.#setOpen(true);
            }
        }, this.openDelayValue);
    }

    hide() {
        this.#clearTimeouts();
        this.closeTimeout = setTimeout(() => {
            this.closeTimeout = null;
            this.#setOpen(false);
        }, this.closeDelayValue);
    }

    #setOpen(open) {
        if (native && this.hasContentTarget) {
            const content = this.contentTarget;
            this.#connectNative(content);
            if (content.matches(':popover-open') !== open) {
                content.togglePopover(open);

                return;
            }
        }
        this.element.dataset.state = open ? 'open' : 'closed';
    }

    // Idempotent, as a Turbo morph strips what it sets.
    #connectNative(content) {
        content.popover = 'manual';
        content.style.positionAnchor = this.#anchor;
        this.element.style.setProperty('anchor-name', this.#anchor);
    }

    #onBeforeToggle = (event) => {
        if ('closed' === event.newState) {
            for (const nested of event.target.querySelectorAll(':popover-open')) {
                if (nested.matches(':popover-open')) {
                    nested.hidePopover();
                }
            }
        }
        this.element.dataset.state = event.newState;
    };

    #clearTimeouts() {
        if (this.openTimeout) {
            clearTimeout(this.openTimeout);
            this.openTimeout = null;
        }
        if (this.closeTimeout) {
            clearTimeout(this.closeTimeout);
            this.closeTimeout = null;
        }
    }
}
