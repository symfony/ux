import { Controller } from '@hotwired/stimulus';

/**
 * Reveals a protected value by fetching it on demand.
 *
 * The value is never part of the initial HTML: it is only fetched, then
 * inserted as plain text (never as HTML), when the user clicks the trigger.
 * Once fetched, the value is kept in memory: hiding and re-showing does not
 * trigger a new request, so it consumes no additional rate limit.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
export default class extends Controller {
    static targets = ['button', 'content', 'value', 'hideButton', 'error'];

    static values = {
        url: String,
        mask: { type: String, default: '••••••' },
        renderHtml: { type: Boolean, default: false },
        toggle: { type: Boolean, default: false },
        revealLabel: { type: String, default: 'Reveal' },
        hideLabel: { type: String, default: 'Hide' },
        loadingLabel: { type: String, default: 'Loading' },
        errorLabel: { type: String, default: 'Unable to disclose.' },
        rateLimitedLabel: { type: String, default: 'Rate limit exceeded. Try again later.' },
    };

    declare readonly urlValue: string;
    declare readonly maskValue: string;
    declare readonly renderHtmlValue: boolean;
    declare readonly toggleValue: boolean;
    declare readonly revealLabelValue: string;
    declare readonly hideLabelValue: string;
    declare readonly loadingLabelValue: string;
    declare readonly errorLabelValue: string;
    declare readonly rateLimitedLabelValue: string;

    declare readonly buttonTarget: HTMLButtonElement;
    declare readonly hasButtonTarget: boolean;
    declare readonly contentTarget: HTMLElement;
    declare readonly hasContentTarget: boolean;
    declare readonly valueTarget: HTMLElement;
    declare readonly hasValueTarget: boolean;
    declare readonly hideButtonTarget: HTMLButtonElement;
    declare readonly hasHideButtonTarget: boolean;
    declare readonly errorTarget: HTMLElement;
    declare readonly hasErrorTarget: boolean;

    private inFlight = false;

    private cachedValue: string | null = null;

    /**
     * Toggle mode only: whether the value is currently disclosed. Drives the
     * single reveal/hide button so it stays in place instead of being hidden
     * and swapped for a separate hide button.
     */
    private revealed = false;

    /**
     * The trigger marked up by the server (the mask or a custom content such
     * as an icon), restored instead of forcing the mask text.
     */
    private originalButtonHtml: string | null = null;

    /**
     * The value target's initial markup (usually the masked placeholder),
     * restored on hide so the masked state comes back instead of a blank slot.
     */
    private originalValueHtml: string | null = null;

    connect() {
        if (this.hasButtonTarget) {
            this.originalButtonHtml = this.buttonTarget.innerHTML;
            this.resetTrigger();
        }
        if (this.hasValueTarget) {
            this.originalValueHtml = this.valueTarget.innerHTML;
        }
        this.clearError();
    }

    private resetTrigger() {
        if (this.hasButtonTarget) {
            this.buttonTarget.disabled = false;
            this.buttonTarget.setAttribute('aria-busy', 'false');
            if (this.toggleValue) {
                this.buttonTarget.removeAttribute('data-disclose-revealed');
                this.buttonTarget.setAttribute('aria-label', this.revealLabelValue);
            }
            this.buttonTarget.innerHTML = this.originalButtonHtml ?? this.maskValue;
        }

        this.revealed = false;
    }

    async reveal() {
        if (this.inFlight) {
            return;
        }

        // The value was already fetched: render it again without hitting the
        // endpoint, so no rate limit token is consumed on re-show.
        if (this.cachedValue !== null) {
            this.displayValue(this.cachedValue);
            this.dispatch('content-loaded', { detail: { value: this.cachedValue } });

            return;
        }

        this.inFlight = true;
        this.dispatch('start');

        if (this.hasButtonTarget) {
            this.buttonTarget.disabled = true;
            this.buttonTarget.setAttribute('aria-busy', 'true');
            // In toggle mode the trigger carries an icon: keep it in place and
            // let CSS show a spinner via [aria-busy], instead of swapping the
            // label text (which would resize the button and shift the layout).
            if (!this.toggleValue) {
                this.buttonTarget.textContent = this.loadingLabelValue;
            }
        }
        this.clearError();

        try {
            const response = await fetch(this.urlValue, { headers: { Accept: 'application/json' } });
            const data = await response.json().catch(() => ({}));

            if (response.status === 429) {
                this.showError(this.rateLimitedLabelValue);
                this.dispatch('rate-limited', { detail: data });

                return;
            }

            if (!response.ok) {
                this.showError(this.errorLabelValue);
                this.dispatch('error', { detail: data });

                return;
            }

            const value = this.renderHtmlValue ? String(data.html ?? data.value ?? '') : String(data.value ?? '');
            this.cachedValue = value;
            this.displayValue(value);
            this.dispatch('content-loaded', { detail: { value } });
        } catch (error) {
            this.showError(this.errorLabelValue);
            this.dispatch('error', { detail: { error: String(error) } });
        } finally {
            this.inFlight = false;
            if (this.hasButtonTarget) {
                // Default mode: the trigger is hidden once revealed, so only
                // restore it when the request did not succeed. Toggle mode
                // never hides the trigger, so reset only on failure (when the
                // value was not revealed).
                if (this.toggleValue ? !this.revealed : !this.buttonTarget.hidden) {
                    this.resetTrigger();
                }
            }
        }
    }

    hide() {
        if (this.hasValueTarget) {
            // Restore the masked placeholder (or the original, empty slot)
            // instead of leaving the slot blank, so hiding returns to the
            // exact pre-reveal state.
            this.valueTarget.innerHTML = this.originalValueHtml ?? '';
        }
        if (this.hasContentTarget) {
            this.contentTarget.hidden = true;
        }
        if (this.hasHideButtonTarget) {
            this.hideButtonTarget.hidden = true;
        }
        if (this.hasButtonTarget) {
            if (!this.toggleValue) {
                this.buttonTarget.hidden = false;
            }
            this.resetTrigger();
        }
        this.clearError();
        this.dispatch('hidden');
    }

    /**
     * Toggle mode: a single trigger that reveals on the first click and hides
     * on the next, staying in place the whole time. Bound as `disclose#toggle`
     * because Stimulus actions are wired at connect and cannot be swapped at
     * runtime, so the branch happens here on the internal `revealed` flag.
     */
    toggle() {
        if (this.revealed) {
            this.hide();
        } else {
            this.reveal();
        }
    }

    private displayValue(value: string) {
        if (this.hasValueTarget) {
            if (this.renderHtmlValue) {
                this.valueTarget.innerHTML = value;
            } else {
                this.valueTarget.textContent = value;
            }
        }
        if (this.hasContentTarget) {
            this.contentTarget.hidden = false;
        }
        if (this.hasHideButtonTarget && !this.toggleValue) {
            this.hideButtonTarget.hidden = false;
        }
        if (this.hasButtonTarget) {
            this.buttonTarget.disabled = false;
            this.buttonTarget.setAttribute('aria-busy', 'false');
            if (this.toggleValue) {
                // Keep the trigger in place: mark it revealed so CSS swaps the
                // icon, and re-label it for the hide action.
                this.buttonTarget.setAttribute('data-disclose-revealed', 'true');
                this.buttonTarget.setAttribute('aria-label', this.hideLabelValue);
                this.revealed = true;
            } else {
                this.buttonTarget.hidden = true;
            }
        }
        // The error area must stay invisible unless an actual error occurs.
        this.clearError();
    }

    private clearError() {
        if (this.hasErrorTarget) {
            this.errorTarget.hidden = true;
            this.errorTarget.textContent = '';
        }
    }

    private showError(message: string) {
        if (this.hasErrorTarget) {
            this.errorTarget.textContent = message;
            this.errorTarget.hidden = false;
        }
    }
}
