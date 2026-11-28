import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger'];

    #openedOnHover = false;

    connect() {
        this.#setOpen(this.#isOpen());
    }

    disconnect() {
        document.removeEventListener('click', this.#onOutsideClick, true);
    }

    toggle() {
        if (this.#isOpen() && !this.#openedOnHover) {
            this.close();
        } else {
            this.#setOpen(true);
        }
    }

    // `dropdown-close` beats the `:focus-within` rule of daisyUI, so the content stays hidden while the trigger has the focus.
    close(event) {
        if (!this.#isOpen()) {
            return;
        }
        event?.preventDefault();
        const hadFocus = this.element.contains(document.activeElement);
        this.#setOpen(false);
        if (hadFocus && this.hasTriggerTarget) {
            this.triggerTarget.focus();
        }
    }

    closeOnFocusOut(event) {
        if (this.#isOpen() && event.relatedTarget && !this.element.contains(event.relatedTarget)) {
            this.#setOpen(false);
        }
    }

    openOnHover() {
        if (!this.#isOpen()) {
            this.#setOpen(true);
            this.#openedOnHover = true;
        }
    }

    closeOnHover() {
        if (this.#openedOnHover) {
            this.close();
        }
    }

    #isOpen() {
        return this.element.dataset.state === 'open';
    }

    #setOpen(open) {
        this.#openedOnHover = false;
        this.element.dataset.state = open ? 'open' : 'closed';
        this.element.classList.toggle('dropdown-open', open);
        this.element.classList.toggle('dropdown-close', !open);
        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', String(open));
        }
        if (open) {
            document.addEventListener('click', this.#onOutsideClick, true);
        } else {
            document.removeEventListener('click', this.#onOutsideClick, true);
        }
    }

    #onOutsideClick = (event) => {
        if (!this.element.contains(event.target)) {
            this.#setOpen(false);
        }
    };
}
