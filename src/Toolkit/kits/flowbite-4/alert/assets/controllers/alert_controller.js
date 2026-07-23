import { Controller } from '@hotwired/stimulus';
import { Dismiss } from 'flowbite';

/**
 * @target alert  The alert element that is dismissed when closed.
 * @action close  Dismisses the alert.
 */
export default class extends Controller {
    alert = null;
    static targets = ['alert'];

    connect() {
        this.alert = new Dismiss(this.alertTarget);
    }

    close() {
        this.alert.hide();
    }
}
