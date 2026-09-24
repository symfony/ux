import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'trigger',
        'value',
        'content',
        'viewport',
        'item',
        'hiddenInput',
        'scrollUpButton',
        'scrollDownButton',
    ];

    static values = {
        open: { type: Boolean, default: false },
        value: { type: String, default: '' },
    };

    _typeahead = '';
    _typeaheadTimeout = null;

    connect() {
        this._onOutsideClick = this._onOutsideClick.bind(this);
        this._syncSelection();
        this._setState(this.openValue);
        if (this.openValue) {
            this.open({ focus: false });
        }
    }

    disconnect() {
        document.removeEventListener('click', this._onOutsideClick, true);
        window.clearTimeout(this._typeaheadTimeout);
    }

    toggle(event) {
        event?.preventDefault();
        if (this.element.dataset.state === 'open') {
            this.close();
        } else {
            this.open();
        }
    }

    open({ focus = true } = {}) {
        this._setState(true);
        this._position();
        document.addEventListener('click', this._onOutsideClick, true);

        requestAnimationFrame(() =>
            requestAnimationFrame(() => {
                this._scrollSelectedIntoView();
                this._syncScrollButtons();
                if (focus) {
                    (this._selectedItem() ?? this._enabledItems()[0])?.focus();
                }
            })
        );
    }

    close({ focus = true } = {}) {
        this._setState(false);
        document.removeEventListener('click', this._onOutsideClick, true);

        if (focus && this.hasTriggerTarget) {
            this.triggerTarget.focus();
        }
    }

    selectItem(event) {
        const item = event.currentTarget;
        if (this._isItemDisabled(item)) {
            event.preventDefault();
            return;
        }

        this.valueValue = item.dataset.value;
        this.dispatch('change', {
            detail: { value: this.valueValue, label: this._labelOf(item) },
            bubbles: true,
        });
        this.close();
    }

    onTriggerKeydown(event) {
        switch (event.key) {
            case 'ArrowDown':
            case 'ArrowUp':
            case 'Enter':
            case ' ':
                event.preventDefault();
                this.open();
                break;
            default:
                if (this._isTypeaheadKey(event)) {
                    event.preventDefault();
                    this.open();
                    requestAnimationFrame(() => requestAnimationFrame(() => this._typeaheadTo(event.key)));
                }
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
                this._focusRelativeItem(1);
                break;
            case 'ArrowUp':
                event.preventDefault();
                this._focusRelativeItem(-1);
                break;
            case 'Home':
                event.preventDefault();
                this._enabledItems()[0]?.focus();
                break;
            case 'End': {
                event.preventDefault();
                const items = this._enabledItems();
                items[items.length - 1]?.focus();
                break;
            }
            case 'Enter':
            case ' ': {
                event.preventDefault();
                const item = this._enabledItems().find((candidate) => candidate === document.activeElement);
                item?.click();
                break;
            }
            case 'Tab':
                this.close({ focus: false });
                break;
            default:
                if (this._isTypeaheadKey(event)) {
                    event.preventDefault();
                    this._typeaheadTo(event.key);
                }
        }
    }

    scrollUp() {
        this._scrollViewportBy(-1);
    }

    scrollDown() {
        this._scrollViewportBy(1);
    }

    onViewportScroll() {
        this._syncScrollButtons();
    }

    valueValueChanged() {
        this._syncSelection();
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
            this.contentTarget.setAttribute('aria-hidden', String(!isOpen));
        }
    }

    _syncSelection() {
        const selected = this._selectedItem();

        for (const item of this.itemTargets) {
            item.setAttribute('aria-selected', String(item === selected));
        }

        if (this.hasValueTarget) {
            const label = selected ? this._labelOf(selected) : '';
            this.valueTarget.textContent = label || this.valueTarget.dataset.placeholder || '';
        }
        if (this.hasTriggerTarget) {
            this.triggerTarget.dataset.hasValue = String(Boolean(selected));
        }
        if (this.hasHiddenInputTarget) {
            this.hiddenInputTarget.value = this.valueValue;
        }
    }

    _position() {
        if (!this.hasContentTarget || !this.hasTriggerTarget) {
            return;
        }

        const content = this.contentTarget;
        content.style.minWidth = `${this.triggerTarget.offsetWidth}px`;

        if (content.dataset.position !== 'item-aligned' || !this.hasViewportTarget) {
            return;
        }

        const anchor = this._selectedItem() ?? this._enabledItems()[0];
        if (!anchor) {
            return;
        }

        content.style.bottom = 'auto';
        content.style.marginTop = '0px';
        content.style.marginBottom = '0px';
        content.style.top = `${this.triggerTarget.offsetTop - (this.viewportTarget.offsetTop + anchor.offsetTop - this.viewportTarget.scrollTop)}px`;

        const margin = 8;
        const rect = content.getBoundingClientRect();
        const overflowTop = margin - rect.top;
        const overflowBottom = rect.bottom - (window.innerHeight - margin);
        const shift = overflowTop > 0 ? overflowTop : overflowBottom > 0 ? -overflowBottom : 0;
        if (shift !== 0) {
            content.style.top = `${parseFloat(content.style.top) + shift}px`;
        }
    }

    _scrollSelectedIntoView() {
        if (this.hasViewportTarget) {
            this._selectedItem()?.scrollIntoView({ block: 'nearest' });
        }
    }

    _scrollViewportBy(direction) {
        if (this.hasViewportTarget) {
            this.viewportTarget.scrollBy({ top: direction * this.viewportTarget.clientHeight * 0.5 });
        }
    }

    _syncScrollButtons() {
        if (!this.hasViewportTarget) {
            return;
        }

        const { scrollTop, clientHeight, scrollHeight } = this.viewportTarget;
        if (this.hasScrollUpButtonTarget) {
            this.scrollUpButtonTarget.hidden = scrollTop <= 0;
        }
        if (this.hasScrollDownButtonTarget) {
            this.scrollDownButtonTarget.hidden = Math.ceil(scrollTop + clientHeight) >= scrollHeight;
        }
    }

    _isTypeaheadKey(event) {
        return event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey;
    }

    _typeaheadTo(key) {
        window.clearTimeout(this._typeaheadTimeout);
        this._typeahead += key.toLowerCase();
        this._typeaheadTimeout = window.setTimeout(() => (this._typeahead = ''), 1000);

        const items = this._enabledItems();
        const match = items.find((item) => this._labelOf(item).toLowerCase().startsWith(this._typeahead));
        match?.focus();
    }

    _labelOf(item) {
        return (item.querySelector('[data-slot="select-item-text"]') ?? item).textContent.trim();
    }

    _selectedItem() {
        return this.itemTargets.find((item) => item.dataset.value === this.valueValue) ?? null;
    }

    _isItemDisabled(item) {
        return !item || item.dataset.disabled === 'true' || item.getAttribute('aria-disabled') === 'true';
    }

    _enabledItems() {
        return this.itemTargets.filter((item) => !this._isItemDisabled(item));
    }

    _focusRelativeItem(offset) {
        const items = this._enabledItems();
        if (items.length === 0) {
            return;
        }

        const index = items.indexOf(document.activeElement);
        if (index === -1) {
            items[offset > 0 ? 0 : items.length - 1].focus();
            return;
        }

        items[(index + offset + items.length) % items.length].focus();
    }

    _onOutsideClick(event) {
        if (!this.element.contains(event.target)) {
            this.close({ focus: false });
        }
    }
}
