import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger', 'content'];
    static values = { active: String };

    connect() {
        // A tab set without panels, like a bar of filters, must not point to missing elements.
        this.triggerTargets.forEach((trigger) => {
            if (!this.contentTargets.some((content) => content.id === trigger.getAttribute('aria-controls'))) {
                trigger.removeAttribute('aria-controls');
            }
        });
    }

    select(event) {
        this.activeValue = event.currentTarget.dataset.value;
    }

    selectLeft(event) {
        this.#selectSibling(event, this.#isRtl() ? 1 : -1);
    }

    selectRight(event) {
        this.#selectSibling(event, this.#isRtl() ? -1 : 1);
    }

    selectFirst() {
        this.#focusAndSelect(this.#enabledTriggers()[0]);
    }

    selectLast() {
        this.#focusAndSelect(this.#enabledTriggers().at(-1));
    }

    activeValueChanged() {
        const triggers = this.triggerTargets;
        const active = triggers.find((trigger) => trigger.dataset.value === this.activeValue);

        triggers.forEach((trigger) => {
            const isActive = trigger === active;
            trigger.setAttribute('aria-selected', isActive ? 'true' : 'false');
            trigger.tabIndex = isActive ? 0 : -1;
        });

        // Without a selected tab, the first one stays reachable with the Tab key.
        if (!active && this.#enabledTriggers()[0]) {
            this.#enabledTriggers()[0].tabIndex = 0;
        }

        this.contentTargets.forEach((content) => {
            content.hidden = content.dataset.value !== this.activeValue;
        });
    }

    #selectSibling(event, step) {
        const triggers = this.#enabledTriggers();
        const index = triggers.indexOf(event.target);
        if (-1 === index) {
            return;
        }

        this.#focusAndSelect(triggers[(index + step + triggers.length) % triggers.length]);
    }

    #focusAndSelect(trigger) {
        if (!trigger) {
            return;
        }

        trigger.focus();
        this.activeValue = trigger.dataset.value;
    }

    #enabledTriggers() {
        return this.triggerTargets.filter((trigger) => !trigger.disabled);
    }

    #isRtl() {
        return 'rtl' === getComputedStyle(this.element).direction;
    }
}
