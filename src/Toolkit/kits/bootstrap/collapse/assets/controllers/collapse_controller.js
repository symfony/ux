import { Controller } from '@hotwired/stimulus';
import { Collapse } from 'bootstrap';

export default class extends Controller {
    static targets = ['trigger', 'content'];

    connect() {
        this._collapse = new Collapse(this.contentTarget, {
            toggle: false,
        });

        this.contentTarget.addEventListener('shown.bs.collapse', this._onShown.bind(this));
        this.contentTarget.addEventListener('hidden.bs.collapse', this._onHidden.bind(this));

        // Sync initial state
        if (this.contentTarget.classList.contains('show')) {
            this._updateTriggers(true);
        }
    }

    disconnect() {
        this.contentTarget.removeEventListener('shown.bs.collapse', this._onShown.bind(this));
        this.contentTarget.removeEventListener('hidden.bs.collapse', this._onHidden.bind(this));
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
        }
    }
}
