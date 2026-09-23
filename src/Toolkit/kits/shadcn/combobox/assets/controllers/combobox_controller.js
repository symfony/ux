import { Controller } from '@hotwired/stimulus';

/**
 * Behavior of the `Combobox` component: filtering, keyboard navigation, single and multiple
 * selection, and the hidden inputs carrying the selection on form submission.
 *
 * The selection is always held as an array of values, even when `multiple` is off, so a single
 * code path drives the items, the chips and the hidden inputs. Labels are read back from the
 * items themselves, so the server only ever has to render values.
 *
 * @value  open          Whether the list is open.
 * @value  multiple      Whether several values can be selected at once, displayed as chips.
 * @value  autoHighlight Whether the first matching item is highlighted while filtering.
 * @value  required      Whether a value must be selected before the form can be submitted.
 * @value  name          The name of the hidden inputs carrying the selection.
 * @value  value         The selected values.
 * @target input         The text input filtering the list and reflecting the selection.
 * @target trigger       The element toggling the list and reflecting its expanded state.
 * @target clear         The element resetting the selection, hidden while nothing is selected.
 * @target content       The floating surface holding the list.
 * @target list          The listbox holding the items.
 * @target item          A selectable item of the list.
 * @target group         A group of items, hidden when the filter empties it.
 * @target chips         The container holding the chips of a multiple selection.
 * @target chip          A chip standing for one selected value.
 * @target chipTemplate  The template cloned to build a chip.
 * @target value         The element displaying the selection when there is no text input.
 * @action toggle        Opens the list, or closes it when it is already open.
 * @action open          Opens the list.
 * @action clear         Resets the selection.
 * @action filter        Narrows the list down to the items matching the typed text.
 * @action select        Adds the clicked item to the selection, or removes it when `multiple` is on.
 * @action highlight     Highlights the item under the pointer.
 * @action removeChip    Removes from the selection the value the clicked chip stands for.
 * @action focusInput    Moves the focus to the text input.
 * @action onInputKeydown Handles the keyboard navigation from the text input.
 * @action handleOutsideClick Closes the list when the click landed outside the component.
 * @action handleEscape  Closes the list and gives the focus back to the text input.
 */
export default class extends Controller {
    static targets = [
        'input',
        'trigger',
        'clear',
        'content',
        'list',
        'item',
        'group',
        'chips',
        'chip',
        'chipTemplate',
        'value',
    ];

    static values = {
        open: { type: Boolean, default: false },
        multiple: { type: Boolean, default: false },
        autoHighlight: { type: Boolean, default: false },
        required: { type: Boolean, default: false },
        name: { type: String, default: '' },
        value: { type: Array, default: [] },
    };

    connect() {
        this.#identifyItems();
        this.#reflectState();
        this.#reflectSelection();
    }

    toggle(event) {
        event?.preventDefault();

        if (this.openValue) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        if (this.openValue) {
            return;
        }

        this.#resetFilter();
        this.#highlight(this.autoHighlightValue ? this.#visibleItems()[0] : null);
        this.openValue = true;
    }

    close() {
        if (!this.openValue) {
            return;
        }

        this.#highlight(null);
        this.openValue = false;

        if (!this.#inputDisplaysSelection) {
            this.#clearQuery();
            this.#resetFilter();
        }
    }

    clear(event) {
        event?.preventDefault();
        event?.stopPropagation();

        this.valueValue = [];
        this.#clearQuery();
        this.#resetFilter();
        this.focusInput();
    }

    filter() {
        const query = this.#query;
        let matched = 0;

        for (const item of this.itemTargets) {
            const matches = '' === query || this.#labelOf(item).toLowerCase().includes(query);
            item.hidden = !matches;
            matched += matches ? 1 : 0;
        }

        for (const group of this.groupTargets) {
            group.hidden = !this.itemTargets.some((item) => !item.hidden && group.contains(item));
        }

        this.#markEmpty(0 === matched);
        this.openValue = true;
        this.#highlight(this.autoHighlightValue ? this.#visibleItems()[0] : null);
    }

    select(event) {
        const item = event.currentTarget;
        if ('true' === item.dataset.disabled) {
            return;
        }

        const value = item.dataset.value;

        if (!this.multipleValue) {
            this.valueValue = [value];
            this.close();
            this.focusInput();

            return;
        }

        this.valueValue = this.valueValue.includes(value)
            ? this.valueValue.filter((selected) => selected !== value)
            : [...this.valueValue, value];

        this.#clearQuery();
        this.#resetFilter();
        this.focusInput();
    }

    highlight(event) {
        const item = event.currentTarget;
        if (item.hidden || 'true' === item.dataset.disabled) {
            return;
        }

        this.#highlight(item);
    }

    removeChip(event) {
        event.preventDefault();
        event.stopPropagation();

        const chip = event.currentTarget.closest('[data-slot="combobox-chip"]');
        if (null === chip) {
            return;
        }

        this.valueValue = this.valueValue.filter((value) => value !== chip.dataset.value);
        this.focusInput();
    }

    focusInput() {
        this.inputTargets[0]?.focus();
    }

    onInputKeydown(event) {
        const items = this.#visibleItems();
        const current = items.indexOf(this.#highlighted);

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                this.open();
                this.#highlight(items[Math.min(current + 1, items.length - 1)] ?? items[0]);
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.open();
                this.#highlight(-1 === current ? items[items.length - 1] : items[Math.max(current - 1, 0)]);
                break;
            case 'Home':
                event.preventDefault();
                this.#highlight(items[0]);
                break;
            case 'End':
                event.preventDefault();
                this.#highlight(items[items.length - 1]);
                break;
            case 'Enter':
                if (this.openValue && null !== this.#highlighted) {
                    event.preventDefault();
                    this.#highlighted.click();
                }
                break;
            case 'Escape':
                event.preventDefault();
                this.close();
                break;
            case 'Tab':
                this.close();
                break;
            case 'Backspace':
                if (this.multipleValue && '' === event.currentTarget.value && this.valueValue.length > 0) {
                    this.valueValue = this.valueValue.slice(0, -1);
                }
                break;
        }
    }

    handleOutsideClick(event) {
        if (!this.openValue || this.element.contains(event.target)) {
            return;
        }

        this.close();
    }

    handleEscape() {
        if (!this.openValue) {
            return;
        }

        this.close();
        this.focusInput();
    }

    openValueChanged() {
        this.#reflectState();
    }

    valueValueChanged() {
        this.#reflectSelection();
    }

    get #query() {
        return (this.inputTargets[0]?.value ?? '').trim().toLowerCase();
    }

    // The text input carries the selection, unless a `Combobox:Value` or chips already display it,
    // in which case it is only a filter and must be left empty.
    get #inputDisplaysSelection() {
        return !this.multipleValue && !this.hasValueTarget;
    }

    get #highlighted() {
        return this.itemTargets.find((item) => item.hasAttribute('data-highlighted')) ?? null;
    }

    #visibleItems() {
        return this.itemTargets.filter((item) => !item.hidden && 'true' !== item.dataset.disabled);
    }

    #labelOf(item) {
        return item.dataset.label || item.textContent.trim();
    }

    #labelFor(value) {
        const item = this.itemTargets.find((candidate) => candidate.dataset.value === value);

        return null == item ? value : this.#labelOf(item);
    }

    // Items are addressed by `aria-activedescendant`, which needs an id; the server does not render
    // one because a value is free-form text and would not always make a valid id. The list id is
    // derived from the `id` prop, so it keeps the generated ids unique across several comboboxes.
    #identifyItems() {
        const prefix = this.listTargets[0]?.id || this.contentTargets[0]?.id || 'combobox';

        this.itemTargets.forEach((item, index) => {
            if ('' === item.id) {
                item.id = `${prefix}-item-${index}`;
            }
        });
    }

    #resetFilter() {
        for (const item of this.itemTargets) {
            item.hidden = false;
        }

        for (const group of this.groupTargets) {
            group.hidden = false;
        }

        this.#markEmpty(false);
    }

    #clearQuery() {
        for (const input of this.inputTargets) {
            input.value = '';
        }
    }

    #markEmpty(empty) {
        for (const element of [...this.contentTargets, ...this.listTargets]) {
            element.toggleAttribute('data-empty', empty);
        }
    }

    #highlight(item) {
        for (const other of this.itemTargets) {
            other.removeAttribute('data-highlighted');
        }

        if (null == item) {
            for (const input of this.inputTargets) {
                input.removeAttribute('aria-activedescendant');
            }

            return;
        }

        item.setAttribute('data-highlighted', '');
        item.scrollIntoView({ block: 'nearest' });

        for (const input of this.inputTargets) {
            input.setAttribute('aria-activedescendant', item.id);
        }
    }

    #reflectState() {
        const open = this.openValue;
        const state = open ? 'open' : 'closed';

        this.element.dataset.state = state;

        for (const trigger of this.triggerTargets) {
            trigger.dataset.state = state;
            trigger.setAttribute('aria-expanded', String(open));
        }

        for (const input of this.inputTargets) {
            input.setAttribute('aria-expanded', String(open));
        }

        for (const content of this.contentTargets) {
            content.dataset.state = state;
            content.setAttribute('aria-hidden', String(!open));
        }
    }

    #reflectSelection() {
        const values = this.valueValue;
        const empty = 0 === values.length;

        for (const item of this.itemTargets) {
            const selected = values.includes(item.dataset.value);
            item.dataset.selected = String(selected);
            item.setAttribute('aria-selected', String(selected));
        }

        for (const value of this.valueTargets) {
            value.dataset.empty = String(empty);
            value.textContent = empty
                ? (value.dataset.placeholder ?? '')
                : values.map((selected) => this.#labelFor(selected)).join(', ');
        }

        for (const clear of this.clearTargets) {
            clear.hidden = empty;
        }

        if (this.multipleValue) {
            this.#reflectChips(values);
        } else {
            for (const input of this.inputTargets) {
                input.value = this.#inputDisplaysSelection && !empty ? this.#labelFor(values[0]) : '';
            }
        }

        for (const input of this.inputTargets) {
            input.required = this.requiredValue && empty;
        }

        this.#reflectHiddenInputs(values);
    }

    #reflectChips(values) {
        if (!this.hasChipsTarget || !this.hasChipTemplateTarget) {
            return;
        }

        for (const chip of this.chipTargets) {
            chip.remove();
        }

        const template = this.chipTemplateTarget.content.firstElementChild;
        if (null === template) {
            return;
        }

        const chips = document.createDocumentFragment();

        for (const value of values) {
            const chip = template.cloneNode(true);
            chip.dataset.value = value;

            const label = chip.querySelector('[data-slot="combobox-chip-label"]');
            if (null !== label) {
                label.textContent = this.#labelFor(value);
            }

            chips.appendChild(chip);
        }

        this.chipsTarget.prepend(chips);
    }

    #reflectHiddenInputs(values) {
        for (const input of this.element.querySelectorAll('[data-combobox-hidden-input]')) {
            input.remove();
        }

        if ('' === this.nameValue) {
            return;
        }

        const name = this.multipleValue ? `${this.nameValue}[]` : this.nameValue;
        const submitted = this.multipleValue ? values : [values[0] ?? ''];

        for (const value of submitted) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            input.setAttribute('data-combobox-hidden-input', '');
            this.element.appendChild(input);
        }
    }
}
