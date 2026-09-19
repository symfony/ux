import { Controller } from '@hotwired/stimulus';

/**
 * Glue between the `Popover` and the `Calendar` composing the `DatePicker`.
 *
 * Both controllers live on the same element, so the popover is driven by writing its own value
 * attribute rather than by holding a reference to it, and the calendar is reached through the
 * Stimulus application registry.
 *
 * Dates are `Y-m-d` strings, the exchange format of the `Calendar`, and are only turned into a
 * `Date` to be formatted or parsed, at UTC midnight so a time zone west of Greenwich can never
 * move the day.
 *
 * @value  locale        The locale used to format the selection.
 * @value  dateStyle     The length of the formatted selection, one of `full`, `long`, `medium` or `short`.
 * @value  separator     The text placed between two formatted dates.
 * @value  closeOnSelect Whether completing the selection closes the popover.
 * @target trigger       The element opening the popover, flagged while nothing is selected.
 * @target value         The element displaying the formatted selection.
 * @target input         The text input mirroring the selection.
 * @action select        Reflects a calendar selection on the trigger, the value and the input.
 * @action parseInput    Moves the calendar to the date typed in the input.
 * @action open          Opens the popover.
 */
export default class extends Controller {
    static targets = ['trigger', 'value', 'input'];

    static values = {
        locale: String,
        dateStyle: { type: String, default: 'long' },
        separator: { type: String, default: ' - ' },
        closeOnSelect: { type: Boolean, default: true },
    };

    // Signature of the selection this controller just pushed onto the calendar, so the
    // `calendar:select` it echoes back is not mistaken for a click on a day.
    #pushed = null;
    #formatter = null;

    select(event) {
        const { selected = [], mode = 'single' } = event.detail ?? {};
        const echo = selected.join(',') === this.#pushed;
        this.#pushed = null;

        this.#reflect(selected, echo);

        if (!echo && this.closeOnSelectValue && this.#isComplete(selected, mode)) {
            this.#setOpen(false);
        }
    }

    parseInput(event) {
        const date = this.#parse(event.currentTarget.value);
        if (null === date) {
            return;
        }

        const calendar = this.#calendar;
        if (null === calendar) {
            return;
        }

        const month = `${date.slice(0, 7)}-01`;
        if (calendar.monthValue !== month) {
            calendar.monthValue = month;
        }

        if (calendar.selectedValue.join(',') !== date) {
            this.#pushed = date;
            calendar.selectedValue = [date];
        }
    }

    open(event) {
        event?.preventDefault();
        this.#setOpen(true);
    }

    get #calendar() {
        const element = this.element.querySelector('[data-slot="calendar"]');
        if (null === element) {
            return null;
        }

        return this.application.getControllerForElementAndIdentifier(element, 'calendar');
    }

    #setOpen(open) {
        this.element.setAttribute('data-popover-open-value', String(open));
    }

    #reflect(selected, skipInput = false) {
        const empty = 0 === selected.length;
        const formatted = selected.map((date) => this.#format(date)).join(this.separatorValue);

        this.element.dataset.empty = String(empty);

        for (const trigger of this.triggerTargets) {
            trigger.dataset.empty = String(empty);
        }

        for (const value of this.valueTargets) {
            value.dataset.empty = String(empty);
            value.textContent = empty ? (value.dataset.placeholder ?? '') : formatted;
        }

        if (skipInput) {
            return;
        }

        for (const input of this.inputTargets) {
            input.value = formatted;
        }
    }

    #isComplete(selected, mode) {
        if (0 === selected.length || 'multiple' === mode) {
            return false;
        }

        return 'range' !== mode || selected.length > 1;
    }

    #format(date) {
        this.#formatter ??= new Intl.DateTimeFormat(this.localeValue || document.documentElement.lang || undefined, {
            dateStyle: this.dateStyleValue,
            timeZone: 'UTC',
        });

        return this.#formatter.format(new Date(`${date}T00:00:00Z`));
    }

    #parse(text) {
        const value = text.trim();
        if ('' === value) {
            return null;
        }

        // `Y-m-d` is parsed as UTC by `Date`, so it is validated as-is rather than read back
        // through the local getters, which would shift the day west of Greenwich.
        if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
            const parsed = new Date(`${value}T00:00:00Z`);

            return !Number.isNaN(parsed.getTime()) && value === parsed.toISOString().slice(0, 10) ? value : null;
        }

        const timestamp = Date.parse(value);

        return Number.isNaN(timestamp) ? null : this.#toDateString(new Date(timestamp));
    }

    #toDateString(date) {
        return [
            date.getFullYear(),
            String(date.getMonth() + 1).padStart(2, '0'),
            String(date.getDate()).padStart(2, '0'),
        ].join('-');
    }
}
