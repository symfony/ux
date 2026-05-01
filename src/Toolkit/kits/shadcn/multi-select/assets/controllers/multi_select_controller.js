import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger', 'dropdown', 'option', 'badgeContainer', 'placeholder', 'input'];
    static values = {
        open: { type: Boolean, default: false },
    };

    /** @type {Map<string, string>} */
    #selected = new Map();

    openValueChanged() {
        this.dropdownTarget.classList.toggle('hidden', !this.openValue);
        this.triggerTarget.setAttribute('aria-expanded', String(this.openValue));
    }

    toggle() {
        this.openValue = !this.openValue;
    }

    /**
     * @param {Event} event
     */
    toggleOption(event) {
        const option = event.currentTarget;
        const { value, label } = option.dataset;

        if (this.#selected.has(value)) {
            this.#selected.delete(value);
            option.setAttribute('aria-selected', 'false');
            option.querySelector('[data-checked]')?.removeAttribute('data-checked');
        } else {
            this.#selected.set(value, label);
            option.setAttribute('aria-selected', 'true');
            option.querySelector('[data-unchecked]')?.setAttribute('data-checked', '');
        }

        this.#renderBadges();
        this.#syncInput();
    }

    /**
     * @param {Event} event
     */
    removeOption(event) {
        event.stopPropagation();
        const { value } = event.currentTarget.dataset;
        this.#selected.delete(value);

        const option = this.optionTargets.find((o) => o.dataset.value === value);
        if (option) {
            option.setAttribute('aria-selected', 'false');
            option.querySelector('[data-checked]')?.removeAttribute('data-checked');
        }

        this.#renderBadges();
        this.#syncInput();
    }

    /**
     * @param {MouseEvent} event
     */
    closeOnOutside(event) {
        if (!this.element.contains(event.target)) {
            this.openValue = false;
        }
    }

    #renderBadges() {
        const container = this.badgeContainerTarget;

        container.querySelectorAll('[data-badge]').forEach((el) => el.remove());

        if (this.#selected.size === 0) {
            this.placeholderTarget.classList.remove('hidden');
            return;
        }

        this.placeholderTarget.classList.add('hidden');

        for (const [value, label] of this.#selected) {
            const badge = document.createElement('span');
            badge.dataset.badge = '';
            badge.className =
                'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium bg-primary text-primary-foreground';
            badge.innerHTML =
                `${label}` +
                `<button type="button" data-action="click->multi-select#removeOption" data-value="${value}" ` +
                `class="ml-0.5 rounded-full hover:opacity-70 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring" ` +
                `aria-label="Remove ${label}">` +
                `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">` +
                `<path d="M18 6 6 18"/><path d="m6 6 12 12"/>` +
                `</svg></button>`;
            container.insertBefore(badge, this.placeholderTarget);
        }
    }

    #syncInput() {
        const select = this.inputTarget;
        select.innerHTML = '';
        for (const [value, label] of this.#selected) {
            const option = document.createElement('option');
            option.value = value;
            option.text = label;
            option.selected = true;
            select.appendChild(option);
        }
    }
}
