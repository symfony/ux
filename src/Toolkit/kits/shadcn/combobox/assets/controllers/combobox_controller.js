import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'trigger',
        'label',
        'popover',
        'search',
        'option',
        'empty',
        'hiddenInput',
        'group',
        'clearButton',
    ];

    static values = {
        value: { type: String, default: '' },
        placeholder: { type: String, default: 'Select option...' },
    };

    #activeIndex = -1;
    #isOpen = false;
    #outsideClickHandler = null;

    connect() {
        this.#outsideClickHandler = this.#onOutsideClick.bind(this);
    }

    disconnect() {
        this.#close();
    }

    toggle() {
        if (this.#isOpen) {
            this.#close();
        } else {
            this.#open();
        }
    }

    clear(event) {
        event.stopPropagation();
        this.valueValue = '';
    }

    onSearch(event) {
        const query = event.target.value.toLowerCase();
        let firstVisibleIndex = -1;
        let visibleCount = 0;

        const targets = this.optionTargets;
        for (let i = 0; i < targets.length; i++) {
            const matches = targets[i].dataset.label.toLowerCase().includes(query);
            targets[i].hidden = !matches;
            if (matches) {
                if (firstVisibleIndex === -1) firstVisibleIndex = i;
                visibleCount++;
            }
        }

        for (const group of this.groupTargets) {
            group.hidden = !targets.filter((o) => group.contains(o)).some((o) => !o.hidden);
        }

        this.emptyTarget.hidden = visibleCount > 0;
        this.#setActive(firstVisibleIndex);
    }

    onSelect(event) {
        this.#selectOption(event.currentTarget);
    }

    onOptionHover(event) {
        const index = this.optionTargets.indexOf(event.currentTarget);
        if (index !== -1 && !event.currentTarget.hidden) {
            this.#setActive(index);
        }
    }

    onTriggerKeydown(event) {
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                this.#open();
                this.#setActive(this.#firstVisibleIndex());
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.#open();
                this.#setActive(this.#lastVisibleIndex());
                break;
            case 'Enter':
            case ' ':
                event.preventDefault();
                this.#open();
                break;
        }
    }

    onSearchKeydown(event) {
        switch (event.key) {
            case 'ArrowDown': {
                event.preventDefault();
                const next = this.#nextVisibleIndex(this.#activeIndex);
                if (next !== -1) this.#setActive(next);
                break;
            }
            case 'ArrowUp': {
                event.preventDefault();
                const prev = this.#prevVisibleIndex(this.#activeIndex);
                if (prev !== -1) this.#setActive(prev);
                break;
            }
            case 'Home':
                event.preventDefault();
                this.#setActive(this.#firstVisibleIndex());
                break;
            case 'End':
                event.preventDefault();
                this.#setActive(this.#lastVisibleIndex());
                break;
            case 'Enter':
                event.preventDefault();
                if (this.#activeIndex !== -1) {
                    this.#selectOption(this.optionTargets[this.#activeIndex]);
                }
                break;
            case 'Escape':
                event.preventDefault();
                this.#close();
                this.triggerTarget.focus();
                break;
            case 'Tab':
                this.#close();
                break;
        }
    }

    valueValueChanged() {
        this.#syncLabel();
        this.#syncCheckIcons();
        if (this.hasHiddenInputTarget) {
            this.hiddenInputTarget.value = this.valueValue;
        }
    }

    #open() {
        if (this.#isOpen) return;
        this.#isOpen = true;

        this.searchTarget.value = '';
        for (const option of this.optionTargets) {
            option.hidden = false;
        }
        for (const group of this.groupTargets) {
            group.hidden = false;
        }
        this.emptyTarget.hidden = true;
        this.#setActive(-1);

        const popover = this.popoverTarget;
        popover.hidden = false;
        popover.dataset.state = 'open';
        this.#positionPopover();
        this.triggerTarget.setAttribute('aria-expanded', 'true');

        document.addEventListener('pointerdown', this.#outsideClickHandler);

        requestAnimationFrame(() => {
            this.searchTarget.focus();
        });
    }

    #close() {
        if (!this.#isOpen) return;
        this.#isOpen = false;

        const popover = this.popoverTarget;
        popover.hidden = true;
        popover.dataset.state = 'closed';
        popover.style.cssText = '';
        this.triggerTarget.setAttribute('aria-expanded', 'false');

        document.removeEventListener('pointerdown', this.#outsideClickHandler);
    }

    #selectOption(option) {
        const { value, label } = option.dataset;
        this.valueValue = value;
        this.dispatch('change', { detail: { value, label }, bubbles: true });
        this.#close();
        this.triggerTarget.focus();
    }

    #syncLabel() {
        if (!this.hasLabelTarget) return;
        const selected = this.hasOptionTarget
            ? this.optionTargets.find((o) => o.dataset.value === this.valueValue)
            : null;
        const label = selected ? selected.dataset.label : '';
        this.labelTarget.textContent = label || this.placeholderValue;
        this.labelTarget.classList.toggle('text-muted-foreground', !label);
        if (this.hasClearButtonTarget) {
            this.clearButtonTarget.hidden = !label;
        }
    }

    #syncCheckIcons() {
        if (!this.hasOptionTarget) return;
        for (const option of this.optionTargets) {
            const selected = option.dataset.value === this.valueValue;
            option.setAttribute('aria-selected', String(selected));
            const icon = option.querySelector('svg');
            if (icon) {
                icon.classList.toggle('opacity-0', !selected);
                icon.classList.toggle('opacity-100', selected);
            }
        }
    }

    #setActive(index) {
        if (this.#activeIndex !== -1 && this.optionTargets[this.#activeIndex]) {
            delete this.optionTargets[this.#activeIndex].dataset.active;
        }

        this.#activeIndex = index;

        if (index === -1) {
            if (this.hasSearchTarget) {
                this.searchTarget.removeAttribute('aria-activedescendant');
            }
            return;
        }

        const option = this.optionTargets[index];
        if (!option) return;

        option.dataset.active = '';
        this.searchTarget.setAttribute('aria-activedescendant', option.id);
        option.scrollIntoView({ block: 'nearest' });
    }

    #firstVisibleIndex() {
        return this.optionTargets.findIndex((o) => !o.hidden);
    }

    #lastVisibleIndex() {
        const targets = this.optionTargets;
        for (let i = targets.length - 1; i >= 0; i--) {
            if (!targets[i].hidden) return i;
        }
        return -1;
    }

    #nextVisibleIndex(from) {
        const targets = this.optionTargets;
        for (let i = from + 1; i < targets.length; i++) {
            if (!targets[i].hidden) return i;
        }
        return from;
    }

    #prevVisibleIndex(from) {
        const targets = this.optionTargets;
        for (let i = from - 1; i >= 0; i--) {
            if (!targets[i].hidden) return i;
        }
        return from;
    }

    #positionPopover() {
        const triggerRect = this.triggerTarget.getBoundingClientRect();
        const popover = this.popoverTarget;
        const popoverHeight = popover.offsetHeight;

        popover.style.position = 'fixed';
        popover.style.width = `${triggerRect.width}px`;
        popover.style.left = `${triggerRect.left}px`;
        popover.style.zIndex = '50';

        const spaceBelow = window.innerHeight - triggerRect.bottom;
        if (spaceBelow < popoverHeight && triggerRect.top > spaceBelow) {
            popover.style.top = `${Math.max(0, triggerRect.top - popoverHeight)}px`;
        } else {
            popover.style.top = `${triggerRect.bottom}px`;
        }
    }

    #onOutsideClick(event) {
        if (!this.element.contains(event.target) && !this.popoverTarget.contains(event.target)) {
            this.#close();
        }
    }
}
