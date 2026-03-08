import { Controller } from '@hotwired/stimulus';
import { Dismiss } from 'flowbite';

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
