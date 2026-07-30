import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        value: { type: String, default: '' },
        placeholder: { type: String, default: 'Select an option' },
        required: Boolean,
        disabled: Boolean,
    };

    static targets = ['trigger', 'content', 'viewport', 'scrollUp', 'scrollDown', 'value', 'item', 'input'];

    connect() {
        this.initialValue = this.valueValue;
        this.openFocus = 'selected';
        this.typeahead = '';
        this.typeaheadTimeout = null;
        this.reposition = this.#position.bind(this);
        this.onFormReset = () => requestAnimationFrame(() => this.#setValue(this.initialValue));
        this.onFormSubmit = this.#validate.bind(this);

        if (this.hasInputTarget && this.inputTarget.form) {
            this.inputTarget.form.addEventListener('reset', this.onFormReset);
            this.inputTarget.form.addEventListener('submit', this.onFormSubmit);
        }

        this.#sync();
    }

    disconnect() {
        clearTimeout(this.typeaheadTimeout);
        this.#removePositionListeners();

        if (this.hasInputTarget && this.inputTarget.form) {
            this.inputTarget.form.removeEventListener('reset', this.onFormReset);
            this.inputTarget.form.removeEventListener('submit', this.onFormSubmit);
        }
    }

    valueValueChanged() {
        this.#sync();
    }

    onToggle(event) {
        const open = 'open' === event.newState;

        this.element.dataset.state = open ? 'open' : 'closed';
        this.triggerTarget.dataset.state = open ? 'open' : 'closed';
        this.contentTarget.dataset.state = open ? 'open' : 'closed';
        this.triggerTarget.setAttribute('aria-expanded', String(open));

        if (!open) {
            this.#removePositionListeners();
            return;
        }

        this.#position();
        this.#addPositionListeners();

        requestAnimationFrame(() => {
            const items = this.#enabledItems();
            const selected = items.find((item) => item.dataset.value === this.valueValue);
            const target = 'last' === this.openFocus ? items.at(-1) : selected || items[0];
            this.openFocus = 'selected';
            target?.scrollIntoView({ block: 'nearest' });
            target?.focus();
            this.#position();
            this.#syncScrollButtons();
        });
    }

    onTriggerKeydown(event) {
        if (this.disabledValue) {
            return;
        }

        if ('ArrowDown' === event.key || 'ArrowUp' === event.key) {
            event.preventDefault();
            this.openFocus = 'ArrowUp' === event.key ? 'last' : 'selected';
            this.contentTarget.showPopover();
            return;
        }

        if (this.#isPrintableKey(event)) {
            event.preventDefault();
            this.contentTarget.showPopover();
            requestAnimationFrame(() => this.#focusByTypeahead(event.key));
        }
    }

    onContentKeydown(event) {
        const items = this.#enabledItems();
        const index = items.indexOf(document.activeElement);

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                items[(index + 1 + items.length) % items.length]?.focus();
                break;
            case 'ArrowUp':
                event.preventDefault();
                items[(index - 1 + items.length) % items.length]?.focus();
                break;
            case 'Home':
                event.preventDefault();
                items[0]?.focus();
                break;
            case 'End':
                event.preventDefault();
                items.at(-1)?.focus();
                break;
            case 'Enter':
            case ' ':
                if (document.activeElement?.matches('[data-slot="select-item"]')) {
                    event.preventDefault();
                    this.#choose(document.activeElement);
                }
                break;
            case 'Tab':
                requestAnimationFrame(() => this.contentTarget.hidePopover());
                break;
            default:
                if (this.#isPrintableKey(event)) {
                    event.preventDefault();
                    this.#focusByTypeahead(event.key);
                }
        }
    }

    select(event) {
        event.preventDefault();
        this.#choose(event.currentTarget);
    }

    focusItem(event) {
        if (!this.#isDisabled(event.currentTarget)) {
            event.currentTarget.focus({ preventScroll: true });
        }
    }

    onScroll() {
        this.#syncScrollButtons();
    }

    scrollUp(event) {
        event.preventDefault();
        this.viewportTarget.scrollBy({ top: -80, behavior: 'smooth' });
    }

    scrollDown(event) {
        event.preventDefault();
        this.viewportTarget.scrollBy({ top: 80, behavior: 'smooth' });
    }

    #choose(item) {
        if (!item || this.#isDisabled(item)) {
            return;
        }

        this.#setValue(item.dataset.value);
        this.triggerTarget.removeAttribute('aria-invalid');

        const eventTarget = this.hasInputTarget ? this.inputTarget : this.element;
        eventTarget.dispatchEvent(new Event('input', { bubbles: true }));
        eventTarget.dispatchEvent(new Event('change', { bubbles: true }));

        this.dispatch('change', {
            detail: {
                value: this.valueValue,
                label: this.#label(item),
            },
        });

        this.contentTarget.hidePopover();
        this.triggerTarget.focus();
    }

    #setValue(value) {
        this.valueValue = value;

        if (this.hasInputTarget) {
            this.inputTarget.value = value;
        }
    }

    #sync() {
        if (!this.hasItemTarget || !this.hasTriggerTarget) {
            return;
        }

        const selected = this.itemTargets.find((item) => item.dataset.value === this.valueValue);

        if (this.hasValueTarget) {
            this.valueTarget.textContent = selected ? this.#label(selected) : this.#placeholder();
        }

        this.triggerTarget.dataset.placeholder = String(!selected);

        for (const item of this.itemTargets) {
            const isSelected = item === selected;
            item.setAttribute('aria-selected', String(isSelected));
            item.querySelector('[data-select-indicator]')?.classList.toggle('hidden', !isSelected);
        }

        if (this.hasInputTarget) {
            this.inputTarget.value = this.valueValue;
        }
    }

    #validate(event) {
        if (!this.requiredValue || this.disabledValue || '' !== this.valueValue) {
            this.triggerTarget.removeAttribute('aria-invalid');
            return;
        }

        event.preventDefault();
        this.triggerTarget.setAttribute('aria-invalid', 'true');
        this.triggerTarget.focus();
    }

    #focusByTypeahead(key) {
        clearTimeout(this.typeaheadTimeout);
        this.typeahead += key.toLocaleLowerCase();

        const items = this.#enabledItems();
        const current = items.indexOf(document.activeElement);
        const ordered = [...items.slice(current + 1), ...items.slice(0, current + 1)];
        const match = ordered.find((item) => this.#label(item).toLocaleLowerCase().startsWith(this.typeahead));
        match?.focus();

        this.typeaheadTimeout = setTimeout(() => {
            this.typeahead = '';
        }, 700);
    }

    #enabledItems() {
        return this.itemTargets.filter((item) => !this.#isDisabled(item));
    }

    #isDisabled(item) {
        return 'true' === item.dataset.disabled || 'true' === item.getAttribute('aria-disabled');
    }

    #label(item) {
        return item.dataset.textValue || item.querySelector('[data-slot="select-item-text"]')?.textContent.trim() || '';
    }

    #placeholder() {
        return this.hasValueTarget
            ? this.valueTarget.dataset.placeholder || this.placeholderValue
            : this.placeholderValue;
    }

    #isPrintableKey(event) {
        return 1 === event.key.length && !event.altKey && !event.ctrlKey && !event.metaKey;
    }

    #addPositionListeners() {
        window.addEventListener('resize', this.reposition);
        window.addEventListener('scroll', this.reposition, true);
    }

    #removePositionListeners() {
        window.removeEventListener('resize', this.reposition);
        window.removeEventListener('scroll', this.reposition, true);
    }

    #position() {
        if (!this.contentTarget.matches(':popover-open')) {
            return;
        }

        const trigger = this.triggerTarget.getBoundingClientRect();
        const content = this.contentTarget.getBoundingClientRect();
        const preferredSide = this.contentTarget.dataset.side;
        const align = this.contentTarget.dataset.align;
        const position = this.contentTarget.dataset.position;
        const direction = getComputedStyle(this.triggerTarget).direction;
        const gap = 4;

        let top;
        let side = preferredSide;

        if ('item-aligned' === position) {
            const selected = this.itemTargets.find((item) => item.dataset.value === this.valueValue);
            const item = selected || this.#enabledItems()[0];
            const viewportTop = this.viewportTarget.offsetTop;
            const itemCenter = item
                ? viewportTop + item.offsetTop - this.viewportTarget.scrollTop + item.offsetHeight / 2
                : content.height / 2;
            top = trigger.top + trigger.height / 2 - itemCenter;
        } else {
            const bottomTop = trigger.bottom + gap;
            const topTop = trigger.top - content.height - gap;
            const fitsBottom = bottomTop + content.height <= window.innerHeight - 8;
            const fitsTop = topTop >= 8;

            if ('bottom' === preferredSide && !fitsBottom && fitsTop) {
                side = 'top';
            } else if ('top' === preferredSide && !fitsTop && fitsBottom) {
                side = 'bottom';
            }

            top = 'top' === side ? topTop : bottomTop;
        }

        let left = 'rtl' === direction ? trigger.right - content.width : trigger.left;
        if ('center' === align) {
            left = trigger.left + (trigger.width - content.width) / 2;
        } else if ('end' === align) {
            left = 'rtl' === direction ? trigger.left : trigger.right - content.width;
        }

        top = Math.max(8, Math.min(top, window.innerHeight - content.height - 8));
        left = Math.max(8, Math.min(left, window.innerWidth - content.width - 8));

        this.contentTarget.dataset.side = side;
        Object.assign(this.contentTarget.style, {
            minWidth: `${trigger.width}px`,
            top: `${top}px`,
            left: `${left}px`,
        });
    }

    #syncScrollButtons() {
        if (!this.hasViewportTarget) {
            return;
        }

        const canScrollUp = this.viewportTarget.scrollTop > 1;
        const canScrollDown =
            this.viewportTarget.scrollTop + this.viewportTarget.clientHeight < this.viewportTarget.scrollHeight - 1;
        let changed = false;

        if (this.hasScrollUpTarget) {
            changed ||= this.scrollUpTarget.hidden === canScrollUp;
            this.scrollUpTarget.hidden = !canScrollUp;
        }

        if (this.hasScrollDownTarget) {
            changed ||= this.scrollDownTarget.hidden === canScrollDown;
            this.scrollDownTarget.hidden = !canScrollDown;
        }

        if (changed) {
            requestAnimationFrame(() => this.#position());
        }
    }
}
