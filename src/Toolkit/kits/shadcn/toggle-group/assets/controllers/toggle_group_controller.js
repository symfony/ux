import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        type: { type: String, default: 'multiple' },
        labelSelector: { type: String, default: '' },
    };

    toggle(event) {
        if (this.typeValue !== 'single') {
            return;
        }

        const clicked = event.currentTarget;
        const toggles = this.element.querySelectorAll('[data-controller~="toggle"]');

        for (const toggle of toggles) {
            if (toggle !== clicked) {
                const controller = this.application.getControllerForElementAndIdentifier(toggle, 'toggle');
                if (controller && controller.pressedValue) {
                    controller.pressedValue = false;
                }
            }
        }

        if (this.labelSelectorValue) {
            const label = document.querySelector(this.labelSelectorValue);
            if (label) {
                const value = clicked.getAttribute('aria-label') || clicked.textContent.trim();
                label.textContent = value.toLowerCase();
            }
        }
    }
}
