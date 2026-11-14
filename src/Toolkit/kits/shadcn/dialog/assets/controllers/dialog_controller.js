import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger', 'dialog'];

    static values = {
        open: Boolean,
    };

    #wasOpen = null;
    #closing = null;
    #invoker = null;
    #requestedReturnValue;
    #pressedOutside = false;

    #onCommand = (event) => this.#handleCommand(event);
    #onCancel = (event) => this.#handleCancel(event);
    #onClose = () => this.#handleClose();
    #onPointerDown = (event) => {
        this.#pressedOutside = this.#isOutside(event);
    };

    // Listened to here rather than through actions, so that Dialog, Sheet and Drawer all get them.
    dialogTargetConnected(dialog) {
        dialog.addEventListener('command', this.#onCommand);
        dialog.addEventListener('cancel', this.#onCancel);
        dialog.addEventListener('close', this.#onClose);
        dialog.addEventListener('pointerdown', this.#onPointerDown);
    }

    dialogTargetDisconnected(dialog) {
        dialog.removeEventListener('command', this.#onCommand);
        dialog.removeEventListener('cancel', this.#onCancel);
        dialog.removeEventListener('close', this.#onClose);
        dialog.removeEventListener('pointerdown', this.#onPointerDown);
    }

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
        // The invoker outlives a removed dialog. The reference stays for a reopen after a move.
        this.#invoker?.setAttribute('aria-expanded', 'false');
    }

    open(event) {
        if (this.#leavesItToTheCommand(event)) {
            return;
        }

        this.#closing = null;
        // Reopened before the exit transition ended: transition back instead.
        this.dialogTarget.removeAttribute('data-closing');
        if (this.dialogTarget.open && !this.dialogTarget.matches(':modal')) {
            this.dialogTarget.close();
        }
        if (!this.dialogTarget.open) {
            this.dialogTarget.showModal();
        }
        this._focusInitialElement();

        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', 'true');
        }
        this.#invoker?.setAttribute('aria-expanded', 'true');
    }

    closeOnClickOutside(event) {
        // A click in the padding of the dialog targets it too.
        const outside = this.#pressedOutside && this.#isOutside(event);
        this.#pressedOutside = false;
        if (outside) {
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

    close(event) {
        if (this.#leavesItToTheCommand(event)) {
            return;
        }

        this.#close();
    }

    _finishClosing(dialog, closing) {
        // open() or disconnect() ran in the meantime and cancelled this closing.
        if (this.#closing !== closing) {
            return;
        }

        this.#closing = null;
        dialog.removeAttribute('data-closing');
        dialog.close(closing.returnValue);
    }

    #handleCommand(event) {
        // A button without a value leaves `returnValue` untouched, as natively.
        const returnValue = event.source?.hasAttribute('value') ? event.source.value : undefined;

        if ('show-modal' === event.command) {
            event.preventDefault();
            if (event.source && !this.triggerTargets.includes(event.source)) {
                this.#invoker = event.source;
            }
            this.open();
        } else if ('close' === event.command) {
            event.preventDefault();
            this.#close(returnValue);
        } else if ('request-close' === event.command) {
            // requestClose() fires a cancelable `cancel` synchronously, handled by #handleCancel().
            event.preventDefault();
            this.#requestedReturnValue = returnValue;
            try {
                this.dialogTarget.requestClose();
            } finally {
                this.#requestedReturnValue = undefined;
            }
        }
    }

    #handleCancel(event) {
        // A `cancel` bubbling from a descendant, such as a file input whose picker was dismissed, or one a listener prevented.
        if (event.target !== this.dialogTarget || event.defaultPrevented) {
            return;
        }

        event.preventDefault();
        this.#close(this.#requestedReturnValue);
    }

    #handleClose() {
        // Reopened before this queued event ran.
        if (this.dialogTarget.open) {
            return;
        }

        this.#collapse();
    }

    #isOutside(event) {
        if (event.target !== this.dialogTarget) {
            return false;
        }

        const box = this.dialogTarget.getBoundingClientRect();

        return (
            event.clientX < box.left ||
            event.clientX > box.right ||
            event.clientY < box.top ||
            event.clientY > box.bottom
        );
    }

    /**
     * Whether the command event of the button opens or closes the dialog, so the click action must not.
     */
    #leavesItToTheCommand(event) {
        const button = event?.currentTarget;
        if (
            !(button instanceof HTMLButtonElement) ||
            button.commandForElement !== this.dialogTarget ||
            !['show-modal', 'close', 'request-close'].includes(button.command)
        ) {
            return false;
        }

        // Chromium runs no command for a button inside a form, whatever its type.
        return null === button.form;
    }

    #close(returnValue) {
        const dialog = this.dialogTarget;
        if (!dialog.open || dialog.hasAttribute('data-closing')) {
            return;
        }

        this.#collapse();

        // Only Chromium can transition a <dialog> out of the top layer, so play the exit
        // transition while it is still open, through [data-closing], then close it.
        dialog.setAttribute('data-closing', '');
        const closing = { returnValue };
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

    #collapse() {
        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', 'false');
        }
        this.#invoker?.setAttribute('aria-expanded', 'false');
        this.#invoker = null;
    }
}
