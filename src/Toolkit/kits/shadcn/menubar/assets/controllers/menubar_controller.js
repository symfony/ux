import { Controller } from '@hotwired/stimulus';

// The native path needs both: a popover without anchor positioning would be centred by the browser.
const native = HTMLElement.prototype.hasOwnProperty('popover') && CSS.supports('position-area', 'block-end');
let anchors = 0;

export default class extends Controller {
    static targets = ['menu', 'trigger', 'content'];

    #anchors = new WeakMap();
    #menus = new WeakMap();

    connect() {
        this._closeOnOutsideClick = this._closeOnOutsideClick.bind(this);
        this.closeAll();
    }

    disconnect() {
        document.removeEventListener('click', this._closeOnOutsideClick, true);
        this.#reflect(null);
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
        const menu = this.#menus.get(content);
        if (menu) {
            menu.style.removeProperty('anchor-name');
            menu.dataset.state = 'closed';
        }
    }

    toggle(event) {
        event?.preventDefault();
        const menu = this._menuFor(event.currentTarget);
        if (!menu) return;

        if (menu.dataset.state === 'open') {
            this.closeAll();
        } else {
            this._open(menu);
        }
    }

    // Once a menu is open, hovering another trigger switches to its menu; while closed, hovering does nothing.
    enter(event) {
        if (!this._hasOpenMenu()) return;

        const menu = this._menuFor(event.currentTarget);
        if (menu && menu.dataset.state !== 'open') {
            this._open(menu);
        }
    }

    closeAll() {
        this.#reflect(null);
        document.removeEventListener('click', this._closeOnOutsideClick, true);
        this.#syncPopovers();
    }

    _open(menu) {
        this.#reflect(menu);
        document.addEventListener('click', this._closeOnOutsideClick, true);
        this.#syncPopovers();
    }

    #reflect(openMenu) {
        for (const menu of this.menuTargets) {
            menu.dataset.state = menu === openMenu ? 'open' : 'closed';
        }
        for (const trigger of this.triggerTargets) {
            trigger.setAttribute('aria-expanded', String(Boolean(openMenu?.contains(trigger))));
        }
    }

    #syncPopovers() {
        if (!native) {
            return;
        }
        for (const content of this.contentTargets) {
            this.#connectNative(content);
            const open = 'open' === this.#menus.get(content)?.dataset.state;
            if (content.matches(':popover-open') !== open) {
                content.togglePopover(open);
            }
        }
    }

    // Idempotent, as a Turbo morph strips what it sets.
    #connectNative(content) {
        const menu = content.closest('[data-slot="menubar-menu"]');
        if (null === menu) {
            return;
        }
        this.#menus.set(content, menu);
        if (!this.#anchors.has(menu)) {
            this.#anchors.set(menu, `--menubar-${++anchors}`);
        }
        content.popover = 'manual';
        content.style.positionAnchor = this.#anchors.get(menu);
        menu.style.setProperty('anchor-name', this.#anchors.get(menu));
    }

    // The browser leaves a `manual` popover open when the one holding it closes.
    #onBeforeToggle = (event) => {
        if ('closed' !== event.newState) {
            return;
        }
        for (const nested of event.target.querySelectorAll(':popover-open')) {
            if (nested.matches(':popover-open')) {
                nested.hidePopover();
            }
        }
        if ('open' === this.#menus.get(event.target)?.dataset.state) {
            this.#reflect(null);
            document.removeEventListener('click', this._closeOnOutsideClick, true);
        }
    };

    _hasOpenMenu() {
        return this.menuTargets.some((menu) => menu.dataset.state === 'open');
    }

    _menuFor(trigger) {
        return this.menuTargets.find((menu) => menu.contains(trigger)) || null;
    }

    _closeOnOutsideClick(event) {
        if (!this.element.contains(event.target)) {
            this.closeAll();
        }
    }
}
