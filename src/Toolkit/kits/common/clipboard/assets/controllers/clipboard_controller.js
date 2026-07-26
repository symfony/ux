import { Controller } from '@hotwired/stimulus';

/**
 * @value      source           Text to copy to the clipboard; when set, it takes precedence over the `source` target.
 * @value      successDuration  How long, in milliseconds, the success feedback stays active after a copy. Defaults to 2000.
 * @target     source           Element to copy from when no `source` value is set: its form value (input, textarea, select) or, failing that, its text content.
 * @target     success          Element revealed briefly after a successful copy, then hidden again. Hidden on connect.
 * @target     idle             Element hidden while the `success` target is showing, then revealed again; pair it with `success` inside the button to swap the label.
 * @css-class  success          Class(es) added to the controller element for the same window, as a CSS-only alternative to the `success`/`idle` targets.
 * @action     copy             Copies the source to the clipboard, then dispatches a `clipboard:copied` event and flashes the success feedback.
 */
export default class extends Controller {
    static targets = ['source', 'success', 'idle'];
    static classes = ['success'];
    static values = {
        source: String,
        successDuration: { type: Number, default: 2000 },
    };

    #timeout;

    connect() {
        if (this.hasSuccessTarget) {
            this.successTarget.hidden = true;
        }
    }

    async copy(event) {
        event.preventDefault();

        const text = this.#text;
        await navigator.clipboard.writeText(text);

        this.dispatch('copied', { detail: { text } });
        this.#flashSuccess();
    }

    get #text() {
        if (this.hasSourceValue) {
            return this.sourceValue;
        }

        // Form controls expose their content as `value`; everything else falls back to text content.
        return this.sourceTarget.value ?? this.sourceTarget.textContent;
    }

    #flashSuccess() {
        if (!this.hasSuccessTarget && !this.hasSuccessClass) {
            return;
        }

        clearTimeout(this.#timeout);
        this.#toggleSuccess(true);
        this.#timeout = setTimeout(() => this.#toggleSuccess(false), this.successDurationValue);
    }

    #toggleSuccess(active) {
        if (this.hasSuccessTarget) {
            this.successTarget.hidden = !active;
        }

        if (this.hasIdleTarget) {
            this.idleTarget.hidden = active;
        }

        this.successClasses.forEach((className) => this.element.classList.toggle(className, active));
    }
}
