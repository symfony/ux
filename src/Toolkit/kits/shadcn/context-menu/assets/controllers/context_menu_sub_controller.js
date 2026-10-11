import { Controller } from '@hotwired/stimulus';

const VIEWPORT_MARGIN = 8;

export default class extends Controller {
    static targets = ['trigger', 'content'];

    static values = {
        openDelay: { type: Number, default: 0 },
        closeDelay: { type: Number, default: 0 },
    };

    connect() {
        this.openTimeout = null;
        this.closeTimeout = null;
        this.#setState('closed');
    }

    disconnect() {
        this.#clearTimeouts();
    }

    show() {
        this.#clearTimeouts();
        this.openTimeout = setTimeout(() => {
            this.#open();
            this.openTimeout = null;
        }, this.openDelayValue);
    }

    hide() {
        this.#clearTimeouts();
        this.closeTimeout = setTimeout(() => {
            this.#setState('closed');
            this.closeTimeout = null;
        }, this.closeDelayValue);
    }

    close(event) {
        if (!event.target.contains(this.element)) {
            return;
        }

        this.#clearTimeouts();
        this.#setState('closed');
    }

    focusOut(event) {
        if (!this.element.contains(event.relatedTarget)) {
            this.hide();
        }
    }

    onKeydown(event) {
        if (!this.hasTriggerTarget || !this.hasContentTarget) {
            return;
        }

        const rtl = getComputedStyle(this.element).direction === 'rtl';
        const enterKey = rtl ? 'ArrowLeft' : 'ArrowRight';
        const exitKey = rtl ? 'ArrowRight' : 'ArrowLeft';

        if (event.target === this.triggerTarget) {
            if ([enterKey, 'Enter', ' '].includes(event.key)) {
                event.preventDefault();
                event.stopPropagation();
                this.#clearTimeouts();
                this.#open();
                this.#items()[0]?.focus();
            }

            return;
        }

        if (event.target.closest('[data-slot="context-menu-sub-content"]') !== this.contentTarget) {
            return;
        }

        const items = this.#items();
        const index = items.indexOf(event.target);
        let next;
        switch (event.key) {
            case exitKey:
                next = this.triggerTarget;
                break;
            case 'ArrowDown':
                next = items[index === -1 ? 0 : (index + 1) % items.length];
                break;
            case 'ArrowUp':
                next = items[index === -1 ? items.length - 1 : (index - 1 + items.length) % items.length];
                break;
            case 'Home':
                next = items[0];
                break;
            case 'End':
                next = items[items.length - 1];
                break;
            default:
                return;
        }

        event.preventDefault();
        event.stopPropagation();
        next?.focus();
    }

    #open() {
        this.#place();
        this.#setState('open');
    }

    #items() {
        return [...this.contentTarget.querySelectorAll('[data-context-menu-target="item"]')].filter(
            (item) =>
                item.closest('[data-slot="context-menu-sub-content"]') === this.contentTarget &&
                item.getAttribute('aria-disabled') !== 'true'
        );
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

    #place() {
        if (!this.hasContentTarget) {
            return;
        }

        const content = this.contentTarget;
        const anchor = this.element.getBoundingClientRect();
        const viewportWidth = document.documentElement.clientWidth;
        const viewportHeight = document.documentElement.clientHeight;
        const rtl = getComputedStyle(this.element).direction === 'rtl';
        const needed = content.offsetWidth + parseFloat(getComputedStyle(content).marginInlineStart);
        const endSpace = rtl ? anchor.left - VIEWPORT_MARGIN : viewportWidth - VIEWPORT_MARGIN - anchor.right;
        const startSpace = rtl ? viewportWidth - VIEWPORT_MARGIN - anchor.right : anchor.left - VIEWPORT_MARGIN;
        const side = needed <= endSpace || endSpace >= startSpace ? 'inline-end' : 'inline-start';

        if (content.dataset.side !== side) {
            content.style.transition = 'none';
            content.dataset.side = side;
            // Commits the new side's closed offset, so the open transition slides from that side.
            content.getBoundingClientRect();
            content.style.transition = '';
        }

        const overflow = anchor.top + content.offsetHeight - (viewportHeight - VIEWPORT_MARGIN);
        const shift = Math.min(Math.max(overflow, 0), Math.max(anchor.top - VIEWPORT_MARGIN, 0));
        content.style.top = shift > 0 ? `${-shift}px` : '';
    }

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
