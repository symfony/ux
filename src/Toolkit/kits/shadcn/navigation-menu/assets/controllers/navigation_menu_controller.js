import { Controller } from '@hotwired/stimulus';

// The native path needs both: a popover without anchor positioning would be centred by the browser.
const native = HTMLElement.prototype.hasOwnProperty('popover') && CSS.supports('position-area', 'block-end');
let anchors = 0;

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])';

export default class extends Controller {
    static targets = ['item', 'trigger', 'content', 'viewport', 'viewportPositioner'];
    static values = {
        openDelay: { type: Number, default: 200 },
        closeDelay: { type: Number, default: 300 },
    };

    contentByItem = new Map();
    #anchor = `--navigation-menu-${++anchors}`;
    #focusingTrigger = false;
    #pointerDownInside = false;

    connect() {
        this.activeIndex = null;
        this.openTimeout = null;
        this.closeTimeout = null;
        this.#setOpen(false);
    }

    disconnect() {
        this.#clearTimeouts();
        this.#reflect(false);
    }

    // A panel moved into the viewport keeps its item index, for a reconnect or a Turbo cache restore.
    contentTargetConnected(content) {
        const item =
            content.closest('[data-navigation-menu-target="item"]') ??
            this.itemTargets[Number(content.dataset.navigationMenuItemIndex)];
        if (!item) {
            return;
        }
        content.dataset.navigationMenuItemIndex = String(this.itemTargets.indexOf(item));
        this.contentByItem.set(item, content);
        if (this.hasViewportTarget && content.parentElement !== this.viewportTarget) {
            this.viewportTarget.appendChild(content);
        }
    }

    contentTargetDisconnected(content) {
        delete content.dataset.active;
        delete content.dataset.motion;
        for (const [item, candidate] of this.contentByItem) {
            if (candidate === content) {
                this.contentByItem.delete(item);
            }
        }
    }

    viewportPositionerTargetConnected(positioner) {
        if (!native) {
            return;
        }
        this.#connectNative(positioner);
        positioner.addEventListener('beforetoggle', this.#onBeforeToggle);
    }

    viewportPositionerTargetDisconnected(positioner) {
        if (!native) {
            return;
        }
        positioner.removeEventListener('beforetoggle', this.#onBeforeToggle);
        positioner.removeAttribute('popover');
        positioner.style.removeProperty('position-anchor');
        positioner.style.removeProperty('inset-inline-start');
        this.element.style.removeProperty('anchor-name');
        const viewport = positioner.querySelector('[data-slot="navigation-menu-viewport"]');
        if (viewport) {
            viewport.dataset.state = 'closed';
        }
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
        if (!trigger || this.#focusingTrigger) {
            return;
        }

        const item = trigger.closest('[data-navigation-menu-target="item"]');
        const content = item && this.contentByItem.get(item);
        this.#clearTimeouts();

        if (content) {
            this.#activate(item, content);
        }
    }

    onPointerDown() {
        this.#pointerDownInside = true;
        setTimeout(() => (this.#pointerDownInside = false));
    }

    onFocusOut(event) {
        // A click on a spot of a panel that cannot take the focus moves it to the body.
        if (null === event.relatedTarget && this.#pointerDownInside) {
            return;
        }
        if (!this.element.contains(event.relatedTarget)) {
            this.#setOpen(false);
        }
    }

    handleEscape() {
        if (this.activeIndex === null) {
            return;
        }

        const trigger = this.#triggerOf(this.itemTargets[this.activeIndex]);
        const focusInPanel = this.hasViewportTarget && this.viewportTarget.contains(document.activeElement);
        this.#clearTimeouts();
        this.#setOpen(false);
        if (focusInPanel && trigger) {
            // The trigger opens its panel when it gets the focus.
            this.#focusingTrigger = true;
            trigger.focus();
            this.#focusingTrigger = false;
        }
    }

    // The panels live in the viewport, after every trigger, so Tab has to be routed between them.
    onKeyDown(event) {
        if (event.key !== 'Tab' || this.activeIndex === null) {
            return;
        }

        const item = this.itemTargets[this.activeIndex];
        const trigger = this.#triggerOf(item);
        const focusables = Array.from(this.contentByItem.get(item)?.querySelectorAll(FOCUSABLE) ?? []);
        if (!trigger || focusables.length === 0) {
            return;
        }

        let target = null;
        if (!event.shiftKey && event.target === trigger) {
            target = focusables[0];
        } else if (event.shiftKey && event.target === focusables[0]) {
            target = trigger;
        } else if (!event.shiftKey && event.target === focusables[focusables.length - 1]) {
            target = this.itemTargets[this.activeIndex + 1]?.querySelector(FOCUSABLE) ?? null;
        }

        if (target) {
            event.preventDefault();
            target.focus();
        }
    }

    #activate(item, content) {
        if (!this.hasViewportTarget || !this.hasViewportPositionerTarget) {
            return;
        }
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

        // A closed popover is not displayed, so its panels have no size to read yet.
        this.#togglePositioner(true);

        // Read layout before writing styles so the writes below don't force extra reflows.
        const width = content.offsetWidth;
        const height = content.offsetHeight;
        const navRect = this.element.getBoundingClientRect();
        const itemRect = item.getBoundingClientRect();
        const rtl = getComputedStyle(this.element).direction === 'rtl';
        const inlineStart = this.#viewportInlineStart(navRect, itemRect, width, rtl);

        this.viewportTarget.style.setProperty('--navigation-menu-viewport-width', `${width}px`);
        this.viewportTarget.style.setProperty('--navigation-menu-viewport-height', `${height}px`);
        const positioner = this.viewportPositionerTarget;
        // A panel that opens goes straight under its trigger: it only slides from one trigger to another.
        const opening = native && this.activeIndex === null;
        if (opening) {
            positioner.style.transition = 'none';
        }
        // Rather than `position-area`, which Firefox misplaces in right-to-left: https://bugzil.la/2019601
        positioner.style.insetInlineStart = positioner.matches('[popover]')
            ? `calc(anchor(inside) + ${inlineStart}px)`
            : `${inlineStart}px`;
        if (opening) {
            positioner.getBoundingClientRect();
            positioner.style.removeProperty('transition');
        }

        if (motion) {
            content.dataset.motion = motion;
            // Commit the off-screen start position before activating, so the switch transitions.
            void content.offsetWidth;
            delete content.dataset.motion;
        }
        content.dataset.active = '';

        this.activeIndex = newIndex;
        this.#reflect(true, item);
    }

    // Anchor to the trigger's inline-start, but shift so the content never overflows the viewport.
    // The edge is rounded to a whole pixel: text on a fractional pixel is not always antialiased the same way.
    #viewportInlineStart(navRect, itemRect, width, rtl) {
        const margin = 8;
        if (rtl) {
            const right = Math.max(width + margin, Math.min(itemRect.right, window.innerWidth - margin));

            return navRect.right - Math.round(right);
        }

        const left = Math.max(margin, Math.min(itemRect.left, window.innerWidth - margin - width));

        return Math.round(left) - navRect.left;
    }

    // Idempotent, as a Turbo morph strips what it sets.
    #connectNative(positioner) {
        if ('manual' !== positioner.popover) {
            // Without a transition, so the closed positioner goes straight to `display: none`.
            positioner.style.transition = 'none';
            positioner.popover = 'manual';
            positioner.getBoundingClientRect();
            positioner.style.removeProperty('transition');
        }
        positioner.style.positionAnchor = this.#anchor;
        this.element.style.setProperty('anchor-name', this.#anchor);
    }

    #togglePositioner(open) {
        if (!native || !this.hasViewportPositionerTarget) {
            return;
        }
        const positioner = this.viewportPositionerTarget;
        this.#connectNative(positioner);
        if (positioner.matches(':popover-open') !== open) {
            positioner.togglePopover(open);
        }
    }

    #onBeforeToggle = (event) => {
        if ('closed' !== event.newState) {
            return;
        }
        for (const nested of event.target.querySelectorAll(':popover-open')) {
            if (nested.matches(':popover-open')) {
                nested.hidePopover();
            }
        }
        if (this.activeIndex !== null) {
            this.#clearTimeouts();
            this.#reflect(false);
        }
    };

    #setOpen(open, activeItem = null) {
        this.#reflect(open, activeItem);
        this.#togglePositioner(open);
    }

    #reflect(open, activeItem = null) {
        if (this.hasViewportTarget) {
            this.viewportTarget.dataset.state = open ? 'open' : 'closed';
        }

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
            this.#triggerOf(item)?.setAttribute('aria-expanded', isActive ? 'true' : 'false');
        });
    }

    #triggerOf(item) {
        return this.triggerTargets.find((trigger) => item?.contains(trigger)) ?? null;
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
