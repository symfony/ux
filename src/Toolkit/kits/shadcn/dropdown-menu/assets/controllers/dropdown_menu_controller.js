import { Controller } from '@hotwired/stimulus';

// The native path needs both: a popover without anchor positioning would be centred by the browser.
const native = HTMLElement.prototype.hasOwnProperty('popover') && CSS.supports('position-area', 'block-end');
let anchors = 0;

export default class extends Controller {
    static targets = ['trigger', 'content', 'item'];

    static values = {
        open: { type: Boolean, default: false },
    };

    #anchor = `--dropdown-menu-${++anchors}`;

    connect() {
        this._onOutsideClick = this._onOutsideClick.bind(this);
        this._setState(this.openValue);
        if (this.openValue) {
            this.open({ focus: false });
        }
    }

    disconnect() {
        document.removeEventListener('click', this._onOutsideClick, true);
        this._setState(false);
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

    toggle(event) {
        event?.preventDefault();
        if (this.element.dataset.state === 'open') {
            this.close();
        } else {
            this.open();
        }
    }

    open({ focus = 'first' } = {}) {
        if (native && this.hasContentTarget) {
            const content = this.contentTarget;
            this.#connectNative(content);
            if (!content.matches(':popover-open')) {
                content.showPopover();
                this.#placeTransformOrigin(content);
            }
        } else {
            this._setState(true);
        }
        document.addEventListener('click', this._onOutsideClick, true);

        if (focus === 'last') {
            this._focusLastItem();
        } else if (focus) {
            this._focusFirstItem();
        }
    }

    close({ focus = true } = {}) {
        if (native && this.hasContentTarget && this.contentTarget.matches(':popover-open')) {
            this.contentTarget.hidePopover();
        } else {
            this._setState(false);
        }
        document.removeEventListener('click', this._onOutsideClick, true);

        if (focus && this.hasTriggerTarget) {
            this.triggerTarget.focus();
        }
    }

    closeFromItem(event) {
        const item = event.currentTarget;
        if (this._isItemDisabled(item)) {
            event.preventDefault();
            return;
        }

        if (item.dataset.closeOnSelect !== 'false') {
            this.close();
        }
    }

    toggleCheckbox(event) {
        const item = event.currentTarget;
        if (this._isItemDisabled(item)) {
            event.preventDefault();
            return;
        }

        const checked = item.getAttribute('aria-checked') !== 'true';
        item.setAttribute('aria-checked', String(checked));

        const indicator = item.querySelector('[data-checkbox-indicator]');
        if (indicator) {
            indicator.hidden = !checked;
        }

        if (item.dataset.closeOnSelect === 'true') {
            this.close();
        }
    }

    selectRadio(event) {
        const item = event.currentTarget;
        if (this._isItemDisabled(item)) {
            event.preventDefault();
            return;
        }

        const group = item.closest('[data-slot="dropdown-menu-radio-group"]');
        if (!group) {
            return;
        }

        group.dataset.value = item.dataset.value;

        for (const radio of group.querySelectorAll('[role="menuitemradio"]')) {
            const selected = radio.dataset.value === item.dataset.value;
            radio.setAttribute('aria-checked', String(selected));

            const indicator = radio.querySelector('[data-radio-indicator]');
            if (indicator) {
                indicator.hidden = !selected;
            }
        }

        if (item.dataset.closeOnSelect === 'true') {
            this.close();
        }
    }

    onTriggerKeydown(event) {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            this.open({ focus: 'first' });
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            this.open({ focus: 'last' });
        }
    }

    onContentKeydown(event) {
        switch (event.key) {
            case 'Escape':
                event.preventDefault();
                this.close();
                break;
            case 'ArrowDown':
                event.preventDefault();
                this._focusNextItem();
                break;
            case 'ArrowUp':
                event.preventDefault();
                this._focusPrevItem();
                break;
            case 'Home':
                event.preventDefault();
                this._focusFirstItem();
                break;
            case 'End':
                event.preventDefault();
                this._focusLastItem();
                break;
            case 'Tab':
                this.close({ focus: false });
                break;
        }
    }

    _setState(isOpen) {
        const state = isOpen ? 'open' : 'closed';
        this.element.dataset.state = state;

        if (this.hasTriggerTarget) {
            this.triggerTarget.dataset.state = state;
            this.triggerTarget.setAttribute('aria-expanded', String(isOpen));
        }
        if (this.hasContentTarget) {
            this.contentTarget.dataset.state = state;
        }
    }

    // Idempotent, as a Turbo morph strips what it sets.
    #connectNative(content) {
        if ('manual' !== content.popover) {
            // Without a transition, so a closed menu goes straight to `display: none`.
            content.style.transition = 'none';
            // `manual`: the browser's light dismiss would also close it on a click elsewhere in the root.
            content.popover = 'manual';
            content.getBoundingClientRect();
            content.style.removeProperty('transition');
        }
        content.style.positionAnchor = this.#anchor;
        this.element.style.setProperty('anchor-name', this.#anchor);
    }

    // The browser can flip the menu to the other side of its anchor, so it grows from the edge next to it.
    #placeTransformOrigin(content) {
        const box = content.getBoundingClientRect();
        const anchor = this.element.getBoundingClientRect();
        const x = Math.min(Math.max((anchor.left + anchor.width / 2 - box.left) / box.width, 0), 1);
        const y = Math.min(Math.max((anchor.top + anchor.height / 2 - box.top) / box.height, 0), 1);
        content.style.transformOrigin = `${x * 100}% ${y * 100}%`;
    }

    // Updates the state before the browser shows or hides the menu, so the exit transition can play.
    #onBeforeToggle = (event) => {
        if ('closed' === event.newState) {
            this.#placeTransformOrigin(event.target);
            for (const submenu of event.target.querySelectorAll(':popover-open')) {
                if (submenu.matches(':popover-open')) {
                    submenu.hidePopover();
                }
            }
        }
        this._setState('open' === event.newState);
    };

    _onOutsideClick(event) {
        if (!this.element.contains(event.target)) {
            this.close({ focus: false });
        }
    }

    _isItemDisabled(item) {
        return (
            !item ||
            item.getAttribute('aria-disabled') === 'true' ||
            item.dataset.disabled === 'true' ||
            item.hasAttribute('disabled')
        );
    }

    _enabledItems() {
        // Exclude items nested in a sub-menu: they are not part of this menu's roving focus.
        return this.itemTargets.filter(
            (item) => !this._isItemDisabled(item) && !item.closest('[data-slot="dropdown-menu-sub-content"]')
        );
    }

    _focusFirstItem() {
        this._enabledItems()[0]?.focus();
    }

    _focusLastItem() {
        const items = this._enabledItems();
        items[items.length - 1]?.focus();
    }

    _focusNextItem() {
        const items = this._enabledItems();
        if (items.length === 0) return;
        const index = items.indexOf(document.activeElement);
        items[index === -1 ? 0 : (index + 1) % items.length].focus();
    }

    _focusPrevItem() {
        const items = this._enabledItems();
        if (items.length === 0) return;
        const index = items.indexOf(document.activeElement);
        items[index === -1 ? items.length - 1 : (index - 1 + items.length) % items.length].focus();
    }
}
