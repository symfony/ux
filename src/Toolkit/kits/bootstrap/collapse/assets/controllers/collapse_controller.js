import { Controller } from '@hotwired/stimulus';
import { Collapse } from 'bootstrap';

export default class extends Controller {
    static targets = ['trigger', 'content'];

    static values = {
        parent: { type: String, default: '' },
    };

    connect() {
        this._collapse = new Collapse(this.contentTarget, {
            toggle: false,
            parent: this.parentValue ? document.querySelector(this.parentValue) : undefined,
        });

        this.contentTarget.addEventListener('show.bs.collapse', this._onShown.bind(this));
        this.contentTarget.addEventListener('hide.bs.collapse', this._onHidden.bind(this));

        // Sync initial state
        if (this.contentTarget.classList.contains('show')) {
            this._updateTriggers(true);
        }
    }

    disconnect() {
        this.contentTarget.removeEventListener('show.bs.collapse', this._onShown.bind(this));
        this.contentTarget.removeEventListener('hide.bs.collapse', this._onHidden.bind(this));
        this._collapse?.dispose();
    }

    toggle() {
        this._collapse.toggle();
    }

    show() {
        this._collapse.show();
    }

    hide() {
        this._collapse.hide();
    }

    _onShown() {
        this._updateTriggers(true);
    }

    _onHidden() {
        this._updateTriggers(false);
    }

    _updateTriggers(expanded) {
        for (const trigger of this.triggerTargets) {
            trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            trigger.classList.toggle('collapsed', !expanded);
        }
    }
}
