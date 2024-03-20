import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['source', 'button']
    static values = {
        source: String,
        animationClass: {type: String, default: 'copied'},
        animationDuration: {type: Number, default: 500}
    }

    copy ({ params: { value } }) {
        const text = value
            ?? (this.hasSourceValue ? this.sourceValue : null)
            ?? (this.hasSourceTarget ? this.sourceTarget.textContent : null)
        ;

        navigator.clipboard.writeText(text).then(() => this.copied())
    }

    startAnimation() {
        if (this.hasButtonTarget) {
            this.buttonTarget.disabled = true;
            this.buttonTarget.classList.add(this.animationClassValue);
        }
        this.element.classList.add(this.animationClassValue);
    }

    stopAnimation() {
        this.element.classList.remove(this.animationClassValue);
        if (this.hasButtonTarget) {
            this.buttonTarget.classList.remove(this.animationClassValue);
            this.buttonTarget.disabled = false;
        }
    }

    copied() {
        clearTimeout(this.timeout);
        this.startAnimation();
        this.timeout = setTimeout(this.stopAnimation.bind(this), this.animationDurationValue);
    }
}
