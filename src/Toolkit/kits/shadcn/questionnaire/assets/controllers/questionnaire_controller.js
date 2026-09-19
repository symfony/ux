import { Controller } from '@hotwired/stimulus';

const LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

export default class extends Controller {
    static targets = [
        'item',
        'progress',
        'progressCurrent',
        'progressTotal',
        'progressStep',
        'answer',
        'choice',
        'choiceLabel',
        'shortcutBadge',
        'freeform',
        'error',
        'previous',
        'skip',
        'next',
        'submit',
    ];
    static values = { activeItem: String, shortcuts: String };

    // The freeform input shares its name with the fixed choices, so while it is empty its name is
    // parked here to keep it from submitting a value that shadows the selected answer.
    _freeformNames = new WeakMap();

    freeformTargetConnected(input) {
        this._freeformNames.set(input, input.getAttribute('name'));
        this._syncFreeformName(input);
    }

    connect() {
        this._assignShortcuts();

        if (!this._itemByName(this.activeItemValue)) {
            const [first] = this._navigableItems();
            this.activeItemValue = first ? first.dataset.name : '';
        }

        this._render();
    }

    activeItemValueChanged() {
        this._render();
    }

    next() {
        if (!this._validate(this.activeItem)) return;

        // Only promote to `answered`, so navigating past a skipped item does not undo the skip.
        if (this._isAnswered(this.activeItem)) {
            this._setStatus(this.activeItem, 'answered');
        }
        this._move(1);
    }

    previous() {
        this._clearError(this.activeItem);
        this._move(-1);
    }

    skip() {
        const item = this.activeItem;
        if (!item) return;

        this._reset(item);
        this._setStatus(item, 'skipped');
        this._clearError(item);
        this._move(1);
    }

    onSubmit(event) {
        if (!this._validate(this.activeItem)) {
            event.preventDefault();
            return;
        }

        if (this._isAnswered(this.activeItem)) {
            this._setStatus(this.activeItem, 'answered');
        }
    }

    onAnswerChange(event) {
        if (!this.answerTargets.includes(event.target)) return;

        const item = this._itemOf(event.target);
        if (!item) return;

        if (this.choiceTargets.includes(event.target)) {
            this._clearFreeform(item);
        }

        this._setStatus(item, this._isAnswered(item) ? 'answered' : 'unanswered');
        this._clearError(item);
        this._render();
    }

    onAnswerInput(event) {
        const input = event.target;
        if (!this.freeformTargets.includes(input)) return;

        const item = this._itemOf(input);
        if (!item) return;

        this._syncFreeformName(input);

        if (input.value && item.dataset.multiple !== 'true') {
            this._choicesOf(item).forEach((choice) => (choice.checked = false));
        }

        this._setStatus(item, this._isAnswered(item) ? 'answered' : 'unanswered');
        this._clearError(item);
        this._render();
    }

    onKeyDown(event) {
        if (event.metaKey || event.ctrlKey || event.altKey) return;

        const item = this.activeItem;
        if (!item) return;

        if (event.key === 'Enter') {
            if (event.target.type === 'submit') return;

            event.preventDefault();
            if (this._isLast(item)) {
                this._form?.requestSubmit();
            } else {
                this.next();
            }
            return;
        }

        if (this.freeformTargets.includes(event.target)) return;

        const choice = this._choicesOf(item).find(
            (input) => this._labelOf(input)?.dataset.shortcut === event.key.toUpperCase()
        );
        if (!choice || choice.disabled) return;

        event.preventDefault();
        choice.checked = item.dataset.multiple === 'true' ? !choice.checked : true;
        choice.dispatchEvent(new Event('change', { bubbles: true }));
    }

    get activeItem() {
        return this._itemByName(this.activeItemValue);
    }

    // The root is a form on its own, or a div nested in the form that owns submission.
    get _form() {
        return this.element.closest('form');
    }

    _move(offset) {
        const items = this._navigableItems();
        const index = items.indexOf(this.activeItem);
        const target = items[index + offset];

        if (target) {
            this.activeItemValue = target.dataset.name;
        }
    }

    _render() {
        const items = this._navigableItems();
        const active = this.activeItem;

        this.itemTargets.forEach((item) => {
            const isActive = item === active;
            item.dataset.active = isActive ? 'true' : 'false';
            item.toggleAttribute('hidden', !isActive);
            item.toggleAttribute('inert', !isActive);
        });

        const current = Math.max(items.indexOf(active) + 1, 1);
        const total = Math.max(items.length, 1);

        this.progressTargets.forEach((progress) => {
            progress.setAttribute('aria-valuenow', String(current));
            progress.setAttribute('aria-valuemax', String(total));
        });
        this.progressCurrentTargets.forEach((element) => (element.textContent = String(current)));
        this.progressTotalTargets.forEach((element) => (element.textContent = String(total)));
        this.progressStepTargets.forEach((step, index) => {
            step.dataset.active = index < current ? 'true' : 'false';
        });

        const first = active === items[0];
        const last = this._isLast(active);

        this._toggle(this.previousTargets, !first);
        this._toggle(this.skipTargets, Boolean(active) && active.dataset.required !== 'true');
        this._toggle(this.nextTargets, !last);
        this._toggle(this.submitTargets, last);
    }

    _toggle(elements, visible) {
        elements.forEach((element) => {
            element.toggleAttribute('hidden', !visible);
            element.disabled = !visible;
        });
    }

    _validate(item) {
        if (!item || item.dataset.required !== 'true' || this._isAnswered(item)) return true;

        this._showError(item);

        return false;
    }

    _showError(item) {
        item.setAttribute('aria-invalid', 'true');
        this._markInvalid(item, true);

        const [error] = this._errorsOf(item);
        if (error) {
            error.removeAttribute('hidden');
            item.setAttribute('aria-describedby', error.id);
        }

        const [answer] = this._answersOf(item).filter((input) => !input.disabled);
        // A programmatic focus never matches `:focus-visible`, so the ring is asked for explicitly.
        answer?.focus({ focusVisible: true });
    }

    _markInvalid(item, invalid) {
        for (const label of this.choiceLabelTargets) {
            if (item.contains(label)) {
                label.dataset.invalid = String(invalid);
            }
        }

        for (const input of this._freeformOf(item)) {
            if (invalid) {
                input.setAttribute('aria-invalid', 'true');
            } else {
                input.removeAttribute('aria-invalid');
            }
        }
    }

    _clearError(item) {
        if (!item || !item.hasAttribute('aria-invalid')) return;

        item.removeAttribute('aria-invalid');
        item.removeAttribute('aria-describedby');
        this._markInvalid(item, false);
        this._errorsOf(item).forEach((error) => error.setAttribute('hidden', ''));
    }

    _reset(item) {
        this._choicesOf(item).forEach((choice) => (choice.checked = false));
        this._clearFreeform(item);
    }

    _clearFreeform(item) {
        this._freeformOf(item).forEach((input) => {
            input.value = '';
            this._syncFreeformName(input);
        });
    }

    _syncFreeformName(input) {
        const name = this._freeformNames.get(input);
        if (!name) return;

        if (input.value.trim()) {
            input.setAttribute('name', name);
        } else {
            input.removeAttribute('name');
        }
    }

    _isAnswered(item) {
        return (
            this._choicesOf(item).some((choice) => choice.checked) ||
            this._freeformOf(item).some((input) => input.value.trim())
        );
    }

    _isLast(item) {
        const items = this._navigableItems();

        return !item || item === items[items.length - 1];
    }

    _setStatus(item, status) {
        item.dataset.status = status;
    }

    _assignShortcuts() {
        const mode = this.shortcutsValue;
        if ('letters' !== mode && 'numbers' !== mode) return;

        this.itemTargets.forEach((item) => {
            this._choicesOf(item).forEach((choice, index) => {
                const label = this._labelOf(choice);
                if (!label) return;

                const shortcut = mode === 'letters' ? LETTERS[index] : mode === 'numbers' ? String(index + 1) : null;

                if (!shortcut) {
                    delete label.dataset.shortcut;

                    return;
                }

                label.dataset.shortcut = shortcut;
                const badge = this.shortcutBadgeTargets.find((element) => label.contains(element));
                if (badge) {
                    badge.textContent = shortcut;
                }
            });
        });
    }

    _navigableItems() {
        return this.itemTargets.filter((item) => !item.disabled);
    }

    _itemByName(name) {
        return name ? this._navigableItems().find((item) => item.dataset.name === name) : undefined;
    }

    _itemOf(element) {
        return this.itemTargets.find((item) => item.contains(element));
    }

    _answersOf(item) {
        return this.answerTargets.filter((input) => item.contains(input));
    }

    _choicesOf(item) {
        return this.choiceTargets.filter((input) => item.contains(input));
    }

    _labelOf(choice) {
        return this.choiceLabelTargets.find((label) => label.contains(choice)) ?? null;
    }

    _freeformOf(item) {
        return this.freeformTargets.filter((input) => item.contains(input));
    }

    _errorsOf(item) {
        return this.errorTargets.filter((error) => item.contains(error));
    }
}
