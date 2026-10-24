import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    show() {
        this.dispatch('show');
    }
}
