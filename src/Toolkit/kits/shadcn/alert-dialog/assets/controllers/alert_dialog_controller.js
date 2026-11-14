import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger', 'dialog'];

    static values = {
        open: Boolean,
    };

    #wasOpen = null;
    #invoker = null;

    connect() {
        if (this.#wasOpen ?? this.openValue) {
            this.open();
        }
    }

    disconnect() {
        // A <dialog> taken out of the DOM comes back open but no longer modal, so reopen it on reconnect.
        this.#wasOpen = this.dialogTarget.open;
        if (this.#wasOpen) {
            this.dialogTarget.close();
        }
        // The invoker outlives a removed dialog. The reference stays for a reopen after a move.
        this.#invoker?.setAttribute('aria-expanded', 'false');
    }

    open() {
        // A trigger runs both its `click` action and, where supported, its `show-modal` command.
        if (this.dialogTarget.open) {
            return;
        }
        this.dialogTarget.showModal();
        this.#invoker?.setAttribute('aria-expanded', 'true');

        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', 'true');
        }
    }

    handleCommand(event) {
        if ('show-modal' === event.command) {
            event.preventDefault();
            if (event.source && !this.triggerTargets.includes(event.source)) {
                this.#invoker = event.source;
            }
            this.open();
        } else if ('close' === event.command || 'request-close' === event.command) {
            event.preventDefault();
            this.close();
        }
    }

    handleCancel(event) {
        // A `cancel` bubbling from a descendant, such as a file input whose picker was dismissed, or one a listener prevented.
        if (event.target !== this.dialogTarget || event.defaultPrevented) {
            return;
        }

        event.preventDefault();
        this.close();
    }

    close() {
        // A cancel button runs both its `click` action and, where supported, its `close` command.
        if (!this.dialogTarget.open) {
            return;
        }
        this.dialogTarget.close();
        this.#invoker?.setAttribute('aria-expanded', 'false');
        this.#invoker = null;

        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', 'false');
        }
    }
}
