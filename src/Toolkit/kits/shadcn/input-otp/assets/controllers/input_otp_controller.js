import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['slot'];

    _patternCache = new Map();

    onInput(event) {
        const slot = event.target;

        if (slot.value.length > 1) {
            slot.value = slot.value.slice(-1);
        }
        if (!slot.value) return;

        // Native `pattern` only validates on submit; enforce it here to block disallowed characters as they are typed.
        if (!this._matchesPattern(slot, slot.value)) {
            slot.value = '';
            return;
        }

        this._focusNext(slot);
    }

    onKeyDown(event) {
        const slots = this.slotTargets;
        const index = slots.indexOf(event.target);

        if (index > 0 && (event.key === 'ArrowLeft' || (event.key === 'Backspace' && !event.target.value))) {
            event.preventDefault();
            this._focusSlot(slots[index - 1]);
            return;
        }

        if (event.key === 'ArrowRight' && index < slots.length - 1) {
            event.preventDefault();
            this._focusSlot(slots[index + 1]);
        }
    }

    onPaste(event) {
        const pasted = (event.clipboardData || window.clipboardData).getData('text');
        if (!pasted) return;
        event.preventDefault();

        const slots = this.slotTargets;
        const startIndex = slots.indexOf(event.target);

        let filled = 0;
        for (const char of pasted) {
            const slot = slots[startIndex + filled];
            if (!slot) break;
            if (!this._matchesPattern(slot, char)) continue;
            slot.value = char;
            filled++;
        }
        if (filled === 0) return;

        this._focusSlot(slots[Math.min(startIndex + filled, slots.length - 1)]);
        this.element.dispatchEvent(new Event('input', { bubbles: true }));
    }

    _focusNext(slot) {
        const slots = this.slotTargets;
        const index = slots.indexOf(slot);
        if (index >= 0 && index < slots.length - 1) {
            this._focusSlot(slots[index + 1]);
        }
    }

    _focusSlot(slot) {
        slot.focus();
        slot.select();
    }

    _matchesPattern(slot, value) {
        const pattern = slot.getAttribute('pattern');
        if (!pattern) return true;

        let regex = this._patternCache.get(pattern);
        if (regex === undefined) {
            try {
                regex = new RegExp(`^(?:${pattern})$`);
            } catch {
                regex = null;
            }
            this._patternCache.set(pattern, regex);
        }
        return regex === null || regex.test(value);
    }
}
