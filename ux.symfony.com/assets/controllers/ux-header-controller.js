import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menuButton'];

    initialize() {
        this.open = false;
    }

    connect() {
        document.body.classList.remove('locked');
    }

    disconnect() {
        document.body.classList.remove('locked');
    }

    menuButtonDisconnected() {
        document.body.classList.remove('locked');
    }

    toggleMenu() {
        this.open = !this.open;
        this.element.classList.toggle('open', this.open);
        document.body.classList.toggle('locked', this.open);
    }
}
