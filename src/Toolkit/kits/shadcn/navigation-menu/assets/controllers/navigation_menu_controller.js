import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['item', 'trigger', 'content', 'viewport', 'viewportPositioner'];
    static values = {
        openDelay: { type: Number, default: 200 },
        closeDelay: { type: Number, default: 300 },
    };

    connect() {
        this.activeIndex = null;
        this.openTimeout = null;
        this.closeTimeout = null;
        this.contentByItem = new Map();
        this.triggerByItem = new Map();

        this.triggerTargets.forEach((trigger) => {
            const item = trigger.closest('[data-navigation-menu-target="item"]');
            if (item) {
                this.triggerByItem.set(item, trigger);
            }
        });

        this.contentTargets.forEach((content) => {
            const item = content.closest('[data-navigation-menu-target="item"]');
            if (item) {
                this.contentByItem.set(item, content);
            }
            this.viewportTarget.appendChild(content);
        });
    }

    disconnect() {
        this.#clearTimeouts();
    }

    open(event) {
        const item = event.currentTarget;
        const content = this.contentByItem.get(item);
        this.#clearTimeouts();

        if (!content) {
            this.scheduleClose();

            return;
        }

        const delay = this.activeIndex === null ? this.openDelayValue : 0;
        this.openTimeout = setTimeout(() => {
            this.#activate(item, content);
            this.openTimeout = null;
        }, delay);
    }

    scheduleClose() {
        this.#clearTimeouts();
        this.closeTimeout = setTimeout(() => {
            this.#setOpen(false);
            this.closeTimeout = null;
        }, this.closeDelayValue);
    }

    cancelClose() {
        this.#clearCloseTimeout();
    }

    onFocusIn(event) {
        const trigger = event.target.closest('[data-navigation-menu-target="trigger"]');
        if (!trigger) {
            return;
        }

        const item = trigger.closest('[data-navigation-menu-target="item"]');
        const content = item && this.contentByItem.get(item);
        this.#clearTimeouts();

        if (content) {
            this.#activate(item, content);
        }
    }

    onFocusOut(event) {
        if (!this.element.contains(event.relatedTarget)) {
            this.#setOpen(false);
        }
    }

    #activate(item, content) {
        const newIndex = this.itemTargets.indexOf(item);
        const motion =
            this.activeIndex === null || newIndex === this.activeIndex
                ? null
                : newIndex > this.activeIndex
                  ? 'from-end'
                  : 'from-start';

        this.contentTargets.forEach((candidate) => {
            if (candidate !== content) {
                delete candidate.dataset.active;
                delete candidate.dataset.motion;
            }
        });

        // Read layout before writing styles so the writes below don't force extra reflows.
        const width = content.offsetWidth;
        const height = content.offsetHeight;
        const navRect = this.element.getBoundingClientRect();
        const itemRect = item.getBoundingClientRect();
        const rtl = getComputedStyle(this.element).direction === 'rtl';
        const inlineStart = this.#viewportInlineStart(navRect, itemRect, width, rtl);

        this.viewportTarget.style.setProperty('--navigation-menu-viewport-width', `${width}px`);
        this.viewportTarget.style.setProperty('--navigation-menu-viewport-height', `${height}px`);
        this.viewportPositionerTarget.style.insetInlineStart = `${inlineStart}px`;

        if (motion) {
            content.dataset.motion = motion;
            // Commit the off-screen start position before activating, so the switch transitions.
            void content.offsetWidth;
            delete content.dataset.motion;
        }
        content.dataset.active = '';

        this.activeIndex = newIndex;
        this.#setOpen(true, item);
    }

    // Anchor to the trigger's inline-start, but shift so the content never overflows the viewport.
    #viewportInlineStart(navRect, itemRect, width, rtl) {
        const margin = 8;
        if (rtl) {
            const right = Math.max(width + margin, Math.min(itemRect.right, window.innerWidth - margin));

            return navRect.right - right;
        }

        const left = Math.max(margin, Math.min(itemRect.left, window.innerWidth - margin - width));

        return left - navRect.left;
    }

    #setOpen(open, activeItem = null) {
        this.viewportTarget.dataset.state = open ? 'open' : 'closed';

        if (!open) {
            this.activeIndex = null;
            this.contentTargets.forEach((content) => {
                delete content.dataset.active;
                delete content.dataset.motion;
            });
        }

        this.itemTargets.forEach((item) => {
            const isActive = open && item === activeItem;
            item.dataset.state = isActive ? 'open' : 'closed';
            const trigger = this.triggerByItem.get(item);
            if (trigger) {
                trigger.setAttribute('aria-expanded', isActive ? 'true' : 'false');
            }
        });
    }

    #clearTimeouts() {
        this.#clearOpenTimeout();
        this.#clearCloseTimeout();
    }

    #clearOpenTimeout() {
        if (this.openTimeout) {
            clearTimeout(this.openTimeout);
            this.openTimeout = null;
        }
    }

    #clearCloseTimeout() {
        if (this.closeTimeout) {
            clearTimeout(this.closeTimeout);
            this.closeTimeout = null;
        }
    }
}
