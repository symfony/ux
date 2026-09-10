import { Controller } from '@hotwired/stimulus';

const DAY = 86400000;

/**
 * Month navigation and date selection for the `Calendar` component.
 *
 * The grid is rendered server-side as a fixed six-by-seven table whose every day state is carried
 * by a `data-*` attribute, so navigating never rewrites markup nor class names: it walks the
 * existing cells and flips their attributes, leaving the Twig template the only source of classes.
 *
 * Dates are `Y-m-d` strings, converted to UTC timestamps only for arithmetic, so a daylight saving
 * transition can never shift a day by one.
 *
 * @value  mode            The selection mode, one of `single`, `multiple` or `range`.
 * @value  locale          The locale used to format the caption and day labels.
 * @value  name            The name of the hidden inputs mirroring the selection.
 * @value  month           The first displayed month, as a `Y-m-d` string.
 * @value  selected        The selected dates, as `Y-m-d` strings.
 * @value  disabled        The dates that cannot be selected, as `Y-m-d` strings.
 * @value  modifiers       Extra flags keyed by name, each holding a list of `Y-m-d` strings.
 * @value  today           The date considered as today, as a `Y-m-d` string.
 * @value  minDate         The earliest selectable date, as a `Y-m-d` string.
 * @value  maxDate         The latest selectable date, as a `Y-m-d` string.
 * @value  startMonth      The earliest month reachable through navigation, as a `Y-m-d` string.
 * @value  endMonth        The latest month reachable through navigation, as a `Y-m-d` string.
 * @value  weekStartsOn    The first day of the week, from `0` for Sunday to `6` for Saturday.
 * @value  numberOfMonths  The number of months displayed side by side.
 * @value  showOutsideDays Whether the days of the surrounding months are displayed.
 * @value  showWeekNumber  Whether the leading week number column is displayed.
 * @value  fixedWeeks      Whether every month always displays six weeks.
 * @target month           A displayed month, holding its caption and its grid.
 * @target previous        The button moving the calendar to the previous month.
 * @target next            The button moving the calendar to the next month.
 * @target input           A hidden input mirroring the selection.
 * @action previousMonth   Moves the calendar one month backwards.
 * @action nextMonth       Moves the calendar one month forwards.
 * @action goToMonth       Moves the calendar to the month and year picked in the dropdowns.
 * @action selectDate      Selects the date carried by the `date` param, or by the clicked day.
 * @action handleKeydown   Moves the focus between days with the arrow, home, end and page keys.
 * @action trackFocus      Reflects the focused day on its cell.
 * @action previewRange    Previews the range being drawn up to the hovered day.
 * @action clearPreview    Drops the range preview.
 */
export default class extends Controller {
    static targets = ['month', 'previous', 'next', 'input'];

    static values = {
        mode: { type: String, default: 'single' },
        locale: String,
        name: String,
        month: String,
        selected: Array,
        disabled: Array,
        modifiers: Object,
        today: String,
        minDate: String,
        maxDate: String,
        startMonth: String,
        endMonth: String,
        weekStartsOn: Number,
        numberOfMonths: { type: Number, default: 1 },
        showOutsideDays: { type: Boolean, default: true },
        showWeekNumber: Boolean,
        fixedWeeks: Boolean,
    };

    #connected = false;
    #focusDate = null;
    #preview = null;
    #rendered = null;
    #formatters = {};

    connect() {
        this.#focusDate =
            this.element.querySelector('[data-slot="calendar-day"] button[tabindex="0"]')?.dataset.day ??
            this.selectedValue[0] ??
            this.monthValue;
        this.#connected = true;
    }

    previousMonth() {
        if (!this.#canGoPrevious) {
            return;
        }

        this.monthValue = this.#addMonths(this.monthValue, -1);
    }

    nextMonth() {
        if (!this.#canGoNext) {
            return;
        }

        this.monthValue = this.#addMonths(this.monthValue, 1);
    }

    goToMonth(event) {
        const monthElement = event.target.closest('[data-slot="calendar-month"]');
        const selects = monthElement?.querySelectorAll('select');
        if (2 !== selects?.length) {
            return;
        }

        const month = String(selects[0].value).padStart(2, '0');
        this.monthValue = this.#addMonths(`${selects[1].value}-${month}-01`, -(event.params.index ?? 0));
    }

    selectDate(event) {
        const date = event.params?.date ?? event.currentTarget.dataset.day;
        if (!date || this.#isDisabled(date)) {
            return;
        }

        this.#preview = null;
        this.#focusDate = date;

        if (!this.#isDisplayed(date)) {
            this.monthValue = `${date.slice(0, 7)}-01`;
        }

        this.selectedValue = this.#nextSelection(date);
    }

    handleKeydown(event) {
        const button = event.target.closest('[data-slot="calendar-day"] button');
        if (!button) {
            return;
        }

        const current = button.dataset.day;
        const weekday = (new Date(this.#parse(current)).getUTCDay() - this.weekStartsOnValue + 7) % 7;
        const rtl = 'rtl' === getComputedStyle(this.element).direction;
        let target;

        switch (event.key) {
            case 'ArrowLeft':
                target = this.#shift(current, rtl ? 1 : -1);
                break;
            case 'ArrowRight':
                target = this.#shift(current, rtl ? -1 : 1);
                break;
            case 'ArrowUp':
                target = this.#shift(current, -7);
                break;
            case 'ArrowDown':
                target = this.#shift(current, 7);
                break;
            case 'Home':
                target = this.#shift(current, -weekday);
                break;
            case 'End':
                target = this.#shift(current, 6 - weekday);
                break;
            case 'PageUp':
                target = this.#clampToMonth(current, -1);
                break;
            case 'PageDown':
                target = this.#clampToMonth(current, 1);
                break;
            default:
                return;
        }

        event.preventDefault();
        this.#focusDate = target;

        // Stimulus reports a value change through a mutation observer, so it lands a microtask
        // too late for the focus below: render right away and let that later call deduplicate.
        if (!this.#isDisplayed(target)) {
            this.monthValue = `${target.slice(0, 7)}-01`;
        }

        this.#render();
        this.#dayButton(target)?.focus();
    }

    trackFocus(event) {
        const focused = 'focusin' === event.type ? event.target.closest('[data-slot="calendar-day"]') : null;

        for (const cell of this.#cells()) {
            cell.dataset.focused = String(cell === focused);
        }

        if (focused) {
            this.#focusDate = focused.dataset.day;
            this.#render();
        }
    }

    previewRange(event) {
        if ('range' !== this.modeValue || 1 !== this.selectedValue.length) {
            return;
        }

        const date = event.target.closest('[data-slot="calendar-day"]')?.dataset.day;
        if (!date || date === this.#preview || this.#isDisabled(date)) {
            return;
        }

        this.#preview = date;
        this.#render();
    }

    clearPreview() {
        if (null === this.#preview) {
            return;
        }

        this.#preview = null;
        this.#render();
    }

    monthValueChanged() {
        if (!this.#connected) {
            return;
        }

        this.#render();
        this.dispatch('month-change', { detail: { month: this.monthValue } });
    }

    selectedValueChanged() {
        if (!this.#connected) {
            return;
        }

        this.#render();
        this.#syncInputs();
        this.dispatch('select', { detail: { selected: this.selectedValue, mode: this.modeValue } });
    }

    get #canGoPrevious() {
        return '' === this.startMonthValue || this.monthValue > this.startMonthValue;
    }

    get #canGoNext() {
        const last = this.#addMonths(this.monthValue, this.numberOfMonthsValue - 1);

        return '' === this.endMonthValue || last < this.endMonthValue;
    }

    get #range() {
        if ('range' !== this.modeValue) {
            return [null, null];
        }

        const [from, to] = this.selectedValue;
        if (from && !to && this.#preview && this.#preview >= from) {
            return [from, this.#preview];
        }

        return [from ?? null, to ?? null];
    }

    #render() {
        const signature = [
            this.monthValue,
            this.selectedValue.join(','),
            this.#preview ?? '',
            this.#focusDate ?? '',
        ].join('|');

        if (signature === this.#rendered) {
            return;
        }

        this.#rendered = signature;
        this.monthTargets.forEach((monthElement, index) => this.#renderMonth(monthElement, index));

        for (const button of this.previousTargets) {
            button.setAttribute('aria-disabled', String(!this.#canGoPrevious));
        }
        for (const button of this.nextTargets) {
            button.setAttribute('aria-disabled', String(!this.#canGoNext));
        }
    }

    #renderMonth(monthElement, index) {
        const monthStart = this.#addMonths(this.monthValue, index);
        const prefix = monthStart.slice(0, 7);
        const caption = this.#format('caption', this.#parse(monthStart));

        monthElement.dataset.month = monthStart;
        monthElement.querySelector('[data-slot="calendar-grid"]')?.setAttribute('aria-label', caption);

        const selects = monthElement.querySelectorAll('select');
        const labels = monthElement.querySelectorAll('[data-slot="calendar-caption-label"]');
        if (2 === selects.length) {
            selects[0].value = String(Number(prefix.slice(5)));
            selects[1].value = prefix.slice(0, 4);
            labels[0].firstChild.textContent = this.#format('monthShort', this.#parse(monthStart));
            labels[1].firstChild.textContent = prefix.slice(0, 4);
        } else if (labels[0]) {
            labels[0].textContent = caption;
        }

        const lead = (new Date(this.#parse(monthStart)).getUTCDay() - this.weekStartsOnValue + 7) % 7;
        const gridStart = this.#parse(monthStart) - lead * DAY;
        const [from, to] = this.#range;
        const modifiers = Object.entries(this.modifiersValue);

        this.#cells(monthElement).forEach((cell, offset) => {
            const timestamp = gridStart + offset * DAY;
            const date = this.#toIso(timestamp);
            const outside = !date.startsWith(prefix);
            const rangeStart = null !== from && date === from;
            const rangeEnd = null !== to && date === to;
            const rangeMiddle = null !== from && null !== to && date > from && date < to;
            const selected =
                'range' === this.modeValue ? rangeStart || rangeEnd || rangeMiddle : this.selectedValue.includes(date);
            const disabled = this.#isDisabled(date);

            cell.dataset.day = date;
            cell.dataset.outside = String(outside);
            cell.dataset.today = String(date === this.todayValue);
            cell.dataset.selected = String(selected);
            cell.dataset.disabled = String(disabled);
            cell.dataset.hidden = String(outside && !this.showOutsideDaysValue);
            cell.dataset.rangeStart = String(rangeStart);
            cell.dataset.rangeMiddle = String(rangeMiddle);
            cell.dataset.rangeEnd = String(rangeEnd);
            cell.setAttribute('aria-selected', String(selected));

            for (const [name, dates] of modifiers) {
                cell.setAttribute(`data-${name}`, String(dates.includes(date)));
            }

            const button = cell.querySelector('button');
            button.textContent = this.#format('day', timestamp);
            button.dataset.day = date;
            button.dataset.calendarDateParam = date;
            button.dataset.selectedSingle = String('range' !== this.modeValue && selected);
            button.dataset.rangeStart = String(rangeStart);
            button.dataset.rangeMiddle = String(rangeMiddle);
            button.dataset.rangeEnd = String(rangeEnd);
            button.setAttribute('aria-label', this.#format('full', timestamp));
            button.disabled = disabled;
            button.tabIndex = date === this.#focusDate ? 0 : -1;
        });

        monthElement.querySelectorAll('[data-slot="calendar-week"]').forEach((row, week) => {
            const start = this.#toIso(gridStart + week * 7 * DAY);
            const end = this.#toIso(gridStart + (week * 7 + 6) * DAY);

            row.dataset.hidden = String(!this.fixedWeeksValue && !start.startsWith(prefix) && !end.startsWith(prefix));
        });

        monthElement.querySelectorAll('[data-slot="calendar-week-number"]').forEach((cell, week) => {
            const monday = gridStart + (week * 7 + ((1 - this.weekStartsOnValue + 7) % 7)) * DAY;

            cell.firstElementChild.textContent = String(this.#isoWeek(monday)).padStart(2, '0');
        });
    }

    #nextSelection(date) {
        if ('multiple' === this.modeValue) {
            return this.selectedValue.includes(date)
                ? this.selectedValue.filter((selected) => selected !== date)
                : [...this.selectedValue, date].sort();
        }

        if ('range' === this.modeValue) {
            const [from, to] = this.selectedValue;

            return !from || to || date < from ? [date] : [from, date];
        }

        return this.selectedValue.includes(date) ? [] : [date];
    }

    #syncInputs() {
        if ('' === this.nameValue) {
            return;
        }

        if ('range' === this.modeValue) {
            const [from, to] = this.selectedValue;
            for (const input of this.inputTargets) {
                input.value = ('from' === input.dataset.role ? from : to) ?? '';
            }

            return;
        }

        if ('multiple' !== this.modeValue) {
            if (this.hasInputTarget) {
                this.inputTarget.value = this.selectedValue[0] ?? '';
            }

            return;
        }

        for (const input of this.inputTargets) {
            input.remove();
        }
        for (const date of this.selectedValue) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `${this.nameValue}[]`;
            input.value = date;
            input.dataset.calendarTarget = 'input';
            this.element.append(input);
        }
    }

    #isDisabled(date) {
        return (
            this.disabledValue.includes(date) ||
            ('' !== this.minDateValue && date < this.minDateValue) ||
            ('' !== this.maxDateValue && date > this.maxDateValue)
        );
    }

    #isDisplayed(date) {
        const month = `${date.slice(0, 7)}-01`;

        return month >= this.monthValue && month <= this.#addMonths(this.monthValue, this.numberOfMonthsValue - 1);
    }

    #cells(root = this.element) {
        return Array.from(root.querySelectorAll('[data-slot="calendar-day"]'));
    }

    #dayButton(date) {
        return this.element.querySelector(`[data-slot="calendar-day"][data-day="${date}"] button`);
    }

    #shift(date, days) {
        return this.#toIso(this.#parse(date) + days * DAY);
    }

    /** Moves by whole months, keeping the day of the month when the target month is shorter. */
    #clampToMonth(date, months) {
        const target = this.#addMonths(`${date.slice(0, 7)}-01`, months);
        const lastDay = new Date(this.#parse(this.#addMonths(target, 1)) - DAY).getUTCDate();

        return `${target.slice(0, 8)}${String(Math.min(Number(date.slice(8)), lastDay)).padStart(2, '0')}`;
    }

    #addMonths(month, count) {
        const total = Number(month.slice(0, 4)) * 12 + Number(month.slice(5, 7)) - 1 + count;

        return `${String(Math.floor(total / 12)).padStart(4, '0')}-${String((total % 12) + 1).padStart(2, '0')}-01`;
    }

    #parse(date) {
        return Date.UTC(Number(date.slice(0, 4)), Number(date.slice(5, 7)) - 1, Number(date.slice(8, 10)));
    }

    #toIso(timestamp) {
        return new Date(timestamp).toISOString().slice(0, 10);
    }

    #isoWeek(timestamp) {
        const date = new Date(timestamp);
        date.setUTCDate(date.getUTCDate() - ((date.getUTCDay() + 6) % 7) + 3);

        const firstThursday = new Date(Date.UTC(date.getUTCFullYear(), 0, 4));
        firstThursday.setUTCDate(firstThursday.getUTCDate() - ((firstThursday.getUTCDay() + 6) % 7) + 3);

        return 1 + Math.round((date.getTime() - firstThursday.getTime()) / (7 * DAY));
    }

    #format(style, timestamp) {
        if (!this.#formatters[style]) {
            const options = {
                caption: { month: 'long', year: 'numeric' },
                monthShort: { month: 'short' },
                day: { day: 'numeric' },
                full: { dateStyle: 'full' },
            }[style];

            this.#formatters[style] = new Intl.DateTimeFormat(this.localeValue || undefined, {
                ...options,
                timeZone: 'UTC',
            });
        }

        return this.#formatters[style].format(timestamp);
    }
}
