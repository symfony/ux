import { Controller } from '@hotwired/stimulus';

// The native path needs both: a popover without anchor positioning would be centred by the browser.
const native = HTMLElement.prototype.hasOwnProperty('popover') && CSS.supports('position-area', 'block-end');
let anchors = 0;

export default class extends Controller {
    static targets = ['trigger', 'content'];

    static values = {
        open: { type: Boolean, default: false },
        name: { type: String, default: '' },
    };

    #connected = false;
    #anchor = `--popover-${++anchors}`;

    connect() {
        this.updateState();
        this.#connected = true;
    }

    disconnect() {
        this.#connected = false;
    }

    contentTargetConnected(content) {
        if (!native) {
            return;
        }
        this.#connectNative(content);
        content.addEventListener('beforetoggle', this.#onBeforeToggle);
        content.addEventListener('toggle', this.#onToggle);
    }

    contentTargetDisconnected(content) {
        if (!native) {
            return;
        }
        content.removeEventListener('beforetoggle', this.#onBeforeToggle);
        content.removeEventListener('toggle', this.#onToggle);
        content.removeAttribute('popover');
        content.style.removeProperty('position-anchor');
        content.style.removeProperty('transform-origin');
        this.element.style.removeProperty('anchor-name');
    }

    toggle(event) {
        event?.preventDefault();
        this.openValue = !this.openValue;
    }

    close() {
        this.openValue = false;
    }

    openValueChanged() {
        if (native && this.hasContentTarget) {
            this.#toggleNative(this.contentTarget);
        } else {
            this.updateState();
        }

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

    updateState(open = this.openValue) {
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

    // Idempotent, as a Turbo morph strips what it sets.
    #connectNative(content) {
        if ('manual' !== content.popover) {
            // Without a transition, so a closed popover goes straight to `display: none`.
            content.style.transition = 'none';
            // `manual`: the browser's light dismiss would also close it on a click elsewhere in the root.
            content.popover = 'manual';
            content.getBoundingClientRect();
            content.style.removeProperty('transition');
        }
        content.style.positionAnchor = this.#anchor;
        this.element.style.setProperty('anchor-name', this.#anchor);
    }

    #toggleNative(content) {
        this.#connectNative(content);
        if (content.matches(':popover-open') !== this.openValue) {
            content.togglePopover(this.openValue);
        }
        if (this.openValue) {
            this.#placeTransformOrigin(content);
        }
    }

    // The browser can flip the content to the other side of its anchor, so it grows from the edge next to it.
    #placeTransformOrigin(content) {
        const box = content.getBoundingClientRect();
        const anchor = this.element.getBoundingClientRect();
        const x = Math.min(Math.max((anchor.left + anchor.width / 2 - box.left) / box.width, 0), 1);
        const y = Math.min(Math.max((anchor.top + anchor.height / 2 - box.top) / box.height, 0), 1);
        content.style.transformOrigin = `${x * 100}% ${y * 100}%`;
    }

    // Updates the state before the browser shows or hides the content, so the exit transition can play.
    #onBeforeToggle = (event) => {
        if ('closed' === event.newState) {
            this.#placeTransformOrigin(event.target);
        }
        this.updateState('open' === event.newState);
    };

    #onToggle = (event) => {
        this.openValue = 'open' === event.newState;
    };

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

        // `visibility` is transitioned, so the content stays `visibility: hidden` (not
        // focusable) for at least one frame after opening, and longer on a busy browser.
        const focusWhenVisible = () => {
            if (!this.openValue) {
                return;
            }
            focusable.focus();
            if (document.activeElement !== focusable) {
                requestAnimationFrame(focusWhenVisible);
            }
        };
        requestAnimationFrame(focusWhenVisible);
    }
}
