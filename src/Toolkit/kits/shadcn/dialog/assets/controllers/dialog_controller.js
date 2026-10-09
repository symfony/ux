import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger', 'dialog'];

    static values = {
        open: Boolean,
    };

    #wasOpen = null;
    #closing = null;

    connect() {
        if (this.#wasOpen ?? this.openValue) {
            this.open();
        }
    }

    disconnect() {
        // A <dialog> taken out of the DOM comes back open but no longer modal, so reopen it on reconnect.
        this.#wasOpen = this.dialogTarget.open && !this.dialogTarget.hasAttribute('data-closing');
        this.#closing = null;
        this.dialogTarget.removeAttribute('data-closing');
        if (this.dialogTarget.open) {
            this.dialogTarget.close();
        }
    }

    open() {
        this.#closing = null;
        // Reopened before the exit transition ended: transition back instead.
        const reopened = this.dialogTarget.open && this.dialogTarget.hasAttribute('data-closing');
        this.dialogTarget.removeAttribute('data-closing');
        if (!reopened) {
            this.dialogTarget.showModal();
        }
        this._focusInitialElement();

        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', 'true');
        }
    }

    closeOnClickOutside({ target }) {
        if (target === this.dialogTarget) {
            this.close();
        }
    }

    _focusInitialElement() {
        // showModal() already focuses an [autofocus] target or the first focusable element;
        // when the author did not opt into autofocus, prefer the first form field instead.
        if (this.dialogTarget.querySelector('[autofocus]')) {
            return;
        }

        const field = this.dialogTarget.querySelector(
            'input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled])'
        );
        field?.focus();
    }

    close() {
        const dialog = this.dialogTarget;
        if (!dialog.open || dialog.hasAttribute('data-closing')) {
            return;
        }

        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', 'false');
        }

        // Only Chromium can transition a <dialog> out of the top layer, so play the exit
        // transition while it is still open, through [data-closing], then close it.
        dialog.setAttribute('data-closing', '');
        const closing = {};
        this.#closing = closing;
        const finish = () => this._finishClosing(dialog, closing);
        // The <dialog> and its ::backdrop only: an endless animation in the content would never let it close.
        const animations = dialog
            .getAnimations({ subtree: true })
            .filter(
                (animation) =>
                    animation.effect?.target === dialog && Number.isFinite(animation.effect.getComputedTiming().endTime)
            );
        if (animations.length === 0) {
            finish();

            return;
        }

        Promise.allSettled(animations.map((animation) => animation.finished)).then(finish);
        // A paused animation never finishes: close anyway once the longest one should have ended.
        setTimeout(
            finish,
            Math.max(...animations.map((animation) => animation.effect.getComputedTiming().endTime)) + 100
        );
    }

    _finishClosing(dialog, closing) {
        // open() or disconnect() ran in the meantime and cancelled this closing.
        if (this.#closing !== closing) {
            return;
        }

        this.#closing = null;
        dialog.removeAttribute('data-closing');
        dialog.close();
    }
}
