import { Controller } from '@hotwired/stimulus';

/**
 * @value  autoClose  Delay in milliseconds after which the element removes itself once connected.
 * @target timerbar   Element whose width animates down to 0 over the close delay to visualize the countdown. It is hidden until a delayed or automatic close starts.
 * @action close      Removes the element. Accepts a `data-closeable-delay-param` (milliseconds) to defer the removal.
 * @action cancel     Cancels a pending delayed or automatic close.
 */
export default class extends Controller {
    static targets = ['timerbar'];
    static values = {
        autoClose: Number, // auto close delay in ms
    };

    #timeout;

    connect() {
        if (this.hasTimerbarTarget) {
            this.timerbarTarget.hidden = true;
        }

        if (this.hasAutoCloseValue) {
            this.#remove(this.autoCloseValue);
        }
    }

    close({ params }) {
        this.#remove(Math.max(0, params.delay || 0));
    }

    cancel() {
        clearTimeout(this.#timeout);

        if (this.hasTimerbarTarget) {
            this.timerbarTarget.hidden = true;
        }
    }

    #remove(delay = 0) {
        // Cancel any pending close so repeated triggers can't race an earlier timeout.
        clearTimeout(this.#timeout);

        if (this.hasTimerbarTarget && delay) {
            const timerbar = this.timerbarTarget;
            timerbar.hidden = false;
            // Snap to full width without animating, then transition down to 0 over the delay.
            timerbar.style.transitionDuration = '0ms';
            timerbar.style.width = '100%';
            setTimeout(() => {
                timerbar.style.transitionDuration = `${delay}ms`;
                timerbar.style.width = '0';
            }, 10);
        }

        this.#timeout = setTimeout(() => this.element.remove(), delay);
    }
}
