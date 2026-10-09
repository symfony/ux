import { Controller } from '@hotwired/stimulus';

const LONG_PRESS_DELAY = 700;
const LONG_PRESS_TOLERANCE = 10;
const VIEWPORT_MARGIN = 8;

export default class extends Controller {
    static targets = ['trigger', 'content', 'item'];

    connect() {
        this._onOutsidePointerDown = this._onOutsidePointerDown.bind(this);
        this._onViewportChange = this._onViewportChange.bind(this);
        this._longPress = null;
        this._setState(false);
    }

    disconnect() {
        this.cancelLongPress();
        this._removeListeners();
    }

    open(event) {
        event.preventDefault();
        this.cancelLongPress();

        const hasPointer = event.clientX !== 0 || event.clientY !== 0;
        const anchor = hasPointer ? { x: event.clientX, y: event.clientY } : this._triggerOrigin();

        this._openAt(anchor);
    }

    close() {
        this._setState(false);
        this._removeListeners();
    }

    startLongPress(event) {
        if (event.pointerType === 'mouse') {
            return;
        }

        this.cancelLongPress();
        const anchor = { x: event.clientX, y: event.clientY };
        this._longPress = {
            anchor,
            timeout: setTimeout(() => {
                this._longPress = null;
                this._openAt(anchor);
            }, LONG_PRESS_DELAY),
        };
    }

    moveLongPress(event) {
        if (!this._longPress) {
            return;
        }

        const { anchor } = this._longPress;
        if (Math.hypot(event.clientX - anchor.x, event.clientY - anchor.y) > LONG_PRESS_TOLERANCE) {
            this.cancelLongPress();
        }
    }

    cancelLongPress() {
        if (this._longPress) {
            clearTimeout(this._longPress.timeout);
            this._longPress = null;
        }
    }

    preventContextMenu(event) {
        event.preventDefault();
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

        const group = item.closest('[data-slot="context-menu-radio-group"]');
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
                event.preventDefault();
                this.close();
                break;
        }
    }

    _openAt(anchor) {
        this._setState(true);
        this._position(anchor);

        document.addEventListener('pointerdown', this._onOutsidePointerDown, true);
        window.addEventListener('resize', this._onViewportChange);
        window.addEventListener('scroll', this._onViewportChange, true);

        // `visibility` is transitioned, so the content may still be `visibility: hidden`
        // (not focusable) until the next frames.
        this._focusContent(2);
    }

    _focusContent(retries) {
        this.contentTarget.focus({ preventScroll: true });

        if (retries > 0 && document.activeElement !== this.contentTarget) {
            requestAnimationFrame(() => this._focusContent(retries - 1));
        }
    }

    _removeListeners() {
        document.removeEventListener('pointerdown', this._onOutsidePointerDown, true);
        window.removeEventListener('resize', this._onViewportChange);
        window.removeEventListener('scroll', this._onViewportChange, true);
    }

    _triggerOrigin() {
        const rect = this.triggerTarget.getBoundingClientRect();

        return { x: rect.left, y: rect.top };
    }

    _position({ x, y }) {
        const content = this.contentTarget;
        const side = content.dataset.side;
        const width = content.offsetWidth;
        const height = content.offsetHeight;
        const viewportWidth = document.documentElement.clientWidth;
        const viewportHeight = document.documentElement.clientHeight;

        let left = side === 'left' ? x - width : x;
        let top = side === 'top' ? y - height : y;
        let originX = side === 'left' ? 'right' : 'left';
        let originY = side === 'top' ? 'bottom' : 'top';

        if (side === 'left' ? left < VIEWPORT_MARGIN : left + width > viewportWidth - VIEWPORT_MARGIN) {
            left = side === 'left' ? x : x - width;
            originX = side === 'left' ? 'left' : 'right';
        }
        if (side === 'top' ? top < VIEWPORT_MARGIN : top + height > viewportHeight - VIEWPORT_MARGIN) {
            top = side === 'top' ? y : y - height;
            originY = side === 'top' ? 'top' : 'bottom';
        }

        left = Math.max(VIEWPORT_MARGIN, Math.min(left, viewportWidth - width - VIEWPORT_MARGIN));
        top = Math.max(VIEWPORT_MARGIN, Math.min(top, viewportHeight - height - VIEWPORT_MARGIN));

        content.style.left = `${left}px`;
        content.style.top = `${top}px`;
        content.style.transformOrigin = `${originX} ${originY}`;
    }

    _setState(isOpen) {
        const state = isOpen ? 'open' : 'closed';
        this.element.dataset.state = state;

        if (this.hasTriggerTarget) {
            this.triggerTarget.dataset.state = state;
        }
        if (this.hasContentTarget) {
            this.contentTarget.dataset.state = state;
        }
    }

    _onOutsidePointerDown(event) {
        if (!this.contentTarget.contains(event.target)) {
            this.close();
        }
    }

    _onViewportChange(event) {
        if (event.type === 'scroll' && this.contentTarget.contains(event.target)) {
            return;
        }

        this.close();
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
            (item) => !this._isItemDisabled(item) && !item.closest('[data-slot="context-menu-sub-content"]')
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
