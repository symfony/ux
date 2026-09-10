import { Controller } from '@hotwired/stimulus';

/**
 * @value  template  The output text, where `%index%` and `%count%` are replaced by the current slide number and the total number of slides.
 * @target output    The element whose text content displays the carousel position.
 * @action update    Writes the carousel's current position into the output target.
 */
export default class extends Controller {
    static targets = ['output'];
    static values = {
        template: { type: String, default: 'Slide %index% of %count%' },
    };

    update(event) {
        const { index, count } = event.detail;

        this.outputTarget.textContent = this.templateValue
            .replace('%index%', String(index + 1))
            .replace('%count%', String(count));
    }
}
