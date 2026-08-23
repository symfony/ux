import { Controller } from '@hotwired/stimulus';

/**
 * @value  separator  The string used to join the thumb values shown in the output.
 * @target output     The element whose text content displays the slider's current values.
 * @action update     Writes the slider's current values into the output target.
 */
export default class extends Controller {
    static targets = ['output'];
    static values = {
        separator: { type: String, default: ', ' },
    };

    update(event) {
        const slider = event.currentTarget;
        const values = Array.from(slider.querySelectorAll('[data-slider-target="input"]')).map((i) => i.value);
        this.outputTarget.textContent = values.join(this.separatorValue);
    }
}
