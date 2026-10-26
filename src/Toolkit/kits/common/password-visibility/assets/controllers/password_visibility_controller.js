import { Controller } from '@hotwired/stimulus';

/**
 * @target  input   Password field that the controller switches between `password` and `text`. It goes back to `password` when its form is submitted or reset, and when the page is left.
 * @target  show    Elements shown while the password is hidden, like an "eye" icon or a "Show" label.
 * @target  hide    Elements shown while the password is visible, like an "eye off" icon or a "Hide" label.
 * @action  toggle  Shows the password as plain text, or hides it again. After a click, the field gets the focus back.
 */
export default class extends Controller {
    static targets = ['input', 'show', 'hide'];

    #listeners;

    connect() {
        // Turbo restores a page from its cache with the password as it was, visible or not.
        this.#setVisible(false);

        // Once submitted as plain text, a password could join the values the browser suggests for text fields.
        this.#listeners = new AbortController();
        const hide = () => this.#setVisible(false);
        const { form } = this.inputTarget;
        form?.addEventListener('submit', hide, { signal: this.#listeners.signal });
        form?.addEventListener('reset', hide, { signal: this.#listeners.signal });
        // Keeps the back/forward cache from restoring the page with the password visible.
        window.addEventListener('pagehide', hide, { signal: this.#listeners.signal });
    }

    disconnect() {
        this.#listeners.abort();
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
