import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { pressed: Boolean };

    connect() {
        if (!this.hasPressedValue) {
            this.pressedValue = this.element.getAttribute('aria-pressed') === 'true';
        }

        this.updateState();
    }

    toggle() {
        this.pressedValue = !this.pressedValue;
    }

    pressedValueChanged() {
        this.updateState();
    }

    updateState() {
        const pressed = this.pressedValue;
        this.element.setAttribute('aria-pressed', String(pressed));
        this.element.dataset.state = pressed ? 'active' : 'inactive';
    }
}
