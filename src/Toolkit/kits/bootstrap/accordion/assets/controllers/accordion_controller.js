import { Controller } from '@hotwired/stimulus';
import { Collapse } from 'bootstrap';

export default class extends Controller {
    static targets = ['item', 'header', 'body'];

    static values = {
        alwaysOpen: { type: Boolean, default: false },
    };

    /** @type {Map<HTMLElement, Collapse>} */
    _collapses = new Map();

    connect() {
        for (const body of this.bodyTargets) {
            const collapse = new Collapse(body, {
                toggle: false,
                parent: !this.alwaysOpenValue ? this.element : undefined,
            });
            this._collapses.set(body, collapse);
        }
    }

    disconnect() {
        for (const collapse of this._collapses.values()) {
            collapse.dispose();
        }
        this._collapses.clear();
    }

    toggle(event) {
        const item = event.currentTarget.closest('[data-accordion-target="item"]');
        if (!item) return;

        const body = item.querySelector('[data-accordion-target="body"]');
        if (!body) return;

        const collapse = this._collapses.get(body);
        if (!collapse) return;

        const header = item.querySelector('[data-accordion-target="header"]');
        const isOpen = body.classList.contains('show');

        if (isOpen) {
            collapse.hide();
            if (header) header.setAttribute('aria-expanded', 'false');
            if (header) header.classList.add('collapsed');
        } else {
            // If not alwaysOpen, close others first (Bootstrap handles this via parent option)
            if (!this.alwaysOpenValue) {
                for (const otherHeader of this.headerTargets) {
                    if (otherHeader !== header) {
                        otherHeader.setAttribute('aria-expanded', 'false');
                        otherHeader.classList.add('collapsed');
                    }
                }
            }
            collapse.show();
            if (header) header.setAttribute('aria-expanded', 'true');
            if (header) header.classList.remove('collapsed');
        }
    }
}
