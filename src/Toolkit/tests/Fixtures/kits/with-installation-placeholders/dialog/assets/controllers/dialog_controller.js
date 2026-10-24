import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    open() {
        this.dispatch('open');
    }

    close() {
        // double quotes on purpose, both quote styles must be detected
        this.dispatch("close", { detail: { reason: 'button' } });
    }

    notify() {
        this.element.dispatchEvent(new CustomEvent('dialog:notified'));
    }
}
