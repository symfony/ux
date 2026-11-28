import { Controller } from '@hotwired/stimulus';

// The labels of the scores, named after the constants of the `PasswordStrength` constraint.
const LABELS = ['Very weak', 'Weak', 'Medium', 'Strong', 'Very strong'];

// The entropy, in bits, a password needs to reach each score above very weak.
const THRESHOLDS = [60, 80, 100, 120];

/**
 * Rates a non-empty password from 0 (very weak) to 4 (very strong).
 *
 * It follows `PasswordStrengthValidator::estimateStrength()` of symfony/validator step by step, so the browser shows the score that the `PasswordStrength` constraint computes on the server.
 */
function estimateStrength(password) {
    // PHP reads the password as UTF-8 bytes, not characters: an `é` counts as two.
    const bytes = new TextEncoder().encode(password);
    const distinct = new Set(bytes);

    const pools = { control: 0, digit: 0, upper: 0, lower: 0, other: 0, symbol: 0 };
    for (const byte of distinct) {
        if (byte < 32 || 127 === byte) {
            pools.control = 33;
        } else if (48 <= byte && byte <= 57) {
            pools.digit = 10;
        } else if (65 <= byte && byte <= 90) {
            pools.upper = 26;
        } else if (97 <= byte && byte <= 122) {
            pools.lower = 26;
        } else if (128 <= byte) {
            pools.other = 128;
        } else {
            pools.symbol = 33;
        }
    }

    const pool = Object.values(pools).reduce((sum, size) => sum + size);
    const entropy = distinct.size * Math.log2(pool) + (bytes.length - distinct.size) * Math.log2(distinct.size);

    return THRESHOLDS.filter((threshold) => entropy >= threshold).length;
}

/**
 * @value  score     Score of the current password, from 0 (very weak) to 4 (very strong). The controller sets it as you type, and removes it while the field is empty. Use it in CSS to style the content of the element.
 * @value  labels    Labels of the five scores, from very weak to very strong, shown by the `label` target. Defaults to `Very weak`, `Weak`, `Medium`, `Strong` and `Very strong`.
 * @value  minScore  Lowest score to accept, from 1 (weak) to 4 (very strong), like the `minScore` option of the `PasswordStrength` constraint. Below it, the `input` target is invalid and the browser refuses to submit its form. When unset, any password is accepted.
 * @value  message   Validation message of a password below `minScore`. By default, it is the message of the `PasswordStrength` constraint.
 * @target input     The password field, rated as you type.
 * @target meter     A `<meter>` or `<progress>` element. Its value is the score, or its minimum while the field is empty, and its `aria-valuetext` is the label of the score.
 * @target label     Element whose text is the label of the score, empty while the field is empty.
 */
export default class extends Controller {
    static targets = ['input', 'meter', 'label'];
    static values = {
        score: Number,
        labels: Array,
        minScore: Number,
        message: { type: String, default: 'The password strength is too low. Please use a stronger password.' },
    };

    inputTargetConnected(input) {
        input.addEventListener('input', this.#rate);
        this.#rate();
    }

    inputTargetDisconnected(input) {
        input.removeEventListener('input', this.#rate);
    }

    // An arrow function, so the same listener can be added to the `input` target and removed from it.
    #rate = () => {
        const password = this.inputTarget.value;
        const score = '' === password ? null : estimateStrength(password);
        const previous = this.hasScoreValue ? this.scoreValue : null;

        // Setting a value to `undefined` removes its attribute: an empty field has no score.
        this.scoreValue = score ?? undefined;
        this.#render(score);

        if (score !== previous) {
            this.dispatch('change', { detail: { score } });
        }
    };

    #render(score) {
        const label = null === score ? '' : ((this.hasLabelsValue ? this.labelsValue : LABELS)[score] ?? '');

        if (this.hasMeterTarget) {
            this.meterTarget.value = score ?? this.meterTarget.min ?? 0;
            if (label) {
                this.meterTarget.setAttribute('aria-valuetext', label);
            } else {
                this.meterTarget.removeAttribute('aria-valuetext');
            }
        }

        // Writing the same text again would make a live region announce it once more.
        if (this.hasLabelTarget && this.labelTarget.textContent !== label) {
            this.labelTarget.textContent = label;
        }

        // An empty field is left to the `required` attribute.
        if (this.hasMinScoreValue) {
            this.inputTarget.setCustomValidity(null !== score && score < this.minScoreValue ? this.messageValue : '');
        }
    }
}
