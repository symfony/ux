import { Controller } from '@hotwired/stimulus';

/**
 * @target output  The form control reflecting the calendar selection.
 * @action update  Writes the calendar selection into the output target.
 */
export default class extends Controller {
    static targets = ['output'];

    update(event) {
        this.outputTarget.value = event.detail.selected.join(', ');
    }
}
