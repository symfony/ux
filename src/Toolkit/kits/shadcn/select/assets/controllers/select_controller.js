import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'trigger',
        'value',
        'content',
        'viewport',
        'item',
        'hiddenSelect',
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

        this._select(item);
        this.close();
    }

    highlightItem(event) {
        const item = event.currentTarget;
        if (event.pointerType === 'mouse' && document.activeElement !== item) {
            item.focus({ preventScroll: true });
        }
    }

    unhighlightItem(event) {
        if (document.activeElement === event.currentTarget) {
            this._focusContent();
        }
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
                    const match = this._typeaheadMatch(event.key, this._selectedItem());
                    if (match) {
                        this._select(match);
                    }
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
                    this._typeaheadMatch(event.key, document.activeElement)?.focus();
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

    _select(item) {
        this.valueValue = item.dataset.value;
        this.dispatch('change', {
            detail: { value: this.valueValue, label: this._labelOf(item) },
            bubbles: true,
        });
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
        if (this.hasHiddenSelectTarget) {
            this.hiddenSelectTarget.options[0].value = this.valueValue;
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

        const margin = 8;
        const trigger = this.triggerTarget;
        const viewport = this.viewportTarget;
        const triggerMiddle = trigger.getBoundingClientRect().top + trigger.offsetHeight / 2;
        const anchorMiddle = () => anchor.offsetTop + anchor.offsetHeight / 2;
        viewport.style.maxHeight = '';
        viewport.scrollTop = 0;
        this._syncScrollButtons();

        // A second pass accounts for the scroll-up button that the first scroll may reveal.
        for (let pass = 0; pass < 2; pass++) {
            const anchorTopInList = anchor.offsetTop - viewport.offsetTop;
            const scrollTop = Math.max(
                0,
                anchorTopInList + anchor.offsetHeight - viewport.clientHeight,
                anchorMiddle() - (triggerMiddle - margin)
            );
            const maxScrollTop = viewport.scrollHeight - viewport.clientHeight;
            if (scrollTop > maxScrollTop) {
                const height = viewport.clientHeight - (scrollTop - maxScrollTop);
                const minHeight = Math.min(anchor.offsetHeight * 5, viewport.scrollHeight);
                viewport.style.maxHeight = `${Math.max(height, minHeight)}px`;
            }
            viewport.scrollTop = scrollTop;
            this._syncScrollButtons();
        }

        const top = trigger.offsetTop + trigger.offsetHeight / 2 - (anchorMiddle() - viewport.scrollTop);
        const topInWindow = this.element.getBoundingClientRect().top + top;
        const overflowTop = margin - topInWindow;
        const overflowBottom = topInWindow + content.offsetHeight - (window.innerHeight - margin);
        const shift = overflowTop > 0 ? overflowTop : overflowBottom > 0 ? -overflowBottom : 0;
        content.style.top = `${top + shift}px`;
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

    _typeaheadMatch(key, current) {
        window.clearTimeout(this._typeaheadTimeout);
        this._typeahead += key.toLowerCase();
        this._typeaheadTimeout = window.setTimeout(() => (this._typeahead = ''), 1000);

        const isRepeatedCharacter = [...this._typeahead].every((character) => character === this._typeahead[0]);
        const search = isRepeatedCharacter ? this._typeahead[0] : this._typeahead;
        const items = this._enabledItems();
        const start = Math.max(items.indexOf(current), 0);
        const candidates = [...items.slice(start), ...items.slice(0, start)].filter(
            (item) => search.length > 1 || item !== current
        );
        const match = candidates.find((item) => this._labelOf(item).toLowerCase().startsWith(search));

        return match && match !== current ? match : null;
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

    _focusContent() {
        if (this.hasContentTarget) {
            this.contentTarget.focus({ preventScroll: true });
        }
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

        items[Math.min(Math.max(index + offset, 0), items.length - 1)].focus();
    }

    _onOutsideClick(event) {
        if (!this.element.contains(event.target)) {
            this.close({ focus: false });
        }
    }
}
