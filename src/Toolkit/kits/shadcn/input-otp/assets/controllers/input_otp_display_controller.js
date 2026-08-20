import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['output', 'empty', 'filled'];

    update(event) {
        const otp = event.currentTarget;
        const slots = Array.from(otp.querySelectorAll('[data-slot="input-otp-slot"]'));
        const value = slots.map((s) => s.value).join('');

        if (this.hasOutputTarget) {
            this.outputTarget.textContent = value;
        }
        if (this.hasEmptyTarget && this.hasFilledTarget) {
            this.emptyTarget.hidden = value !== '';
            this.filledTarget.hidden = value === '';
        }
    }
}
