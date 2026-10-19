import { Controller } from '@hotwired/stimulus';

/**
 * @target  input   Password field that the controller switches between `password` and `text`. It goes back to `password` when its form is submitted or reset.
 * @target  show    Elements shown while the password is hidden, like an "eye" icon or a "Show" label.
 * @target  hide    Elements shown while the password is visible, like an "eye off" icon or a "Hide" label.
 * @action  toggle  Shows the password as plain text, or hides it again. After a click, the field gets the focus back.
 */
export default class extends Controller {
    static targets = ['input', 'show', 'hide'];

    #formListeners;

    connect() {
        // Once submitted as plain text, a password could join the values the browser suggests for text fields.
        this.#formListeners = new AbortController();
        const { form } = this.inputTarget;
        form?.addEventListener('submit', () => this.#setVisible(false), { signal: this.#formListeners.signal });
        form?.addEventListener('reset', () => this.#setVisible(false), { signal: this.#formListeners.signal });
    }

    disconnect() {
        this.#formListeners.abort();
    }

    toggle(event) {
        // Keeps a button without a type from submitting its form.
        event.preventDefault();

        this.#setVisible('password' === this.inputTarget.type);

        // A click made with the keyboard has a `detail` of 0: the focus then stays on the button.
        if (event.detail > 0) {
            this.inputTarget.focus();
        }
    }

    #setVisible(visible) {
        this.inputTarget.type = visible ? 'text' : 'password';
        // An `<svg>` has no `hidden` property, only the attribute.
        this.showTargets.forEach((element) => element.toggleAttribute('hidden', visible));
        this.hideTargets.forEach((element) => element.toggleAttribute('hidden', !visible));
    }
}
