import { Controller } from '@hotwired/stimulus';

// The native path needs both: a popover without anchor positioning would be centred by the browser.
const native = HTMLElement.prototype.hasOwnProperty('popover') && CSS.supports('position-area', 'block-end');
let anchors = 0;

export default class extends Controller {
    static targets = ['trigger', 'content'];

    static values = {
        openDelay: { type: Number, default: 0 },
        closeDelay: { type: Number, default: 0 },
    };

    #anchor = `--dropdown-menu-sub-${++anchors}`;

    connect() {
        this.openTimeout = null;
        this.closeTimeout = null;
        this.#setState('closed');
    }

    disconnect() {
        this.#clearTimeouts();
        this.#setState('closed');
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
        content.style.removeProperty('transform-origin');
        this.element.style.removeProperty('anchor-name');
    }

    show() {
        this.#clearTimeouts();
        this.openTimeout = setTimeout(() => {
            this.openTimeout = null;
            // The pointer can cross the submenu trigger while the menu plays its exit transition.
            if ('open' === this.element.closest('[data-slot="dropdown-menu"]')?.dataset.state) {
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
                if (open) {
                    this.#placeTransformOrigin(content);
                }

                return;
            }
        }
        this.#setState(open ? 'open' : 'closed');
    }

    // State lives on the trigger and content directly (not a parent group) so nested
    // sub-menus each react only to their own state.
    #setState(state) {
        this.element.dataset.state = state;
        if (this.hasTriggerTarget) {
            this.triggerTarget.dataset.state = state;
        }
        if (this.hasContentTarget) {
            this.contentTarget.dataset.state = state;
        }
    }

    // Idempotent, as a Turbo morph strips what it sets.
    #connectNative(content) {
        if ('manual' !== content.popover) {
            // Without a transition, so a closed submenu goes straight to `display: none`.
            content.style.transition = 'none';
            content.popover = 'manual';
            content.getBoundingClientRect();
            content.style.removeProperty('transition');
        }
        content.style.positionAnchor = this.#anchor;
        this.element.style.setProperty('anchor-name', this.#anchor);
    }

    // The browser can flip the submenu to the other side of its anchor, so it grows from the edge next to it.
    #placeTransformOrigin(content) {
        const box = content.getBoundingClientRect();
        const anchor = this.element.getBoundingClientRect();
        const x = Math.min(Math.max((anchor.left + anchor.width / 2 - box.left) / box.width, 0), 1);
        const y = Math.min(Math.max((anchor.top + anchor.height / 2 - box.top) / box.height, 0), 1);
        content.style.transformOrigin = `${x * 100}% ${y * 100}%`;
    }

    #onBeforeToggle = (event) => {
        if ('closed' === event.newState) {
            this.#placeTransformOrigin(event.target);
            for (const submenu of event.target.querySelectorAll(':popover-open')) {
                if (submenu.matches(':popover-open')) {
                    submenu.hidePopover();
                }
            }
        }
        this.#setState(event.newState);
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
