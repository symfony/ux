import { Controller } from '@hotwired/stimulus';
import Tooltip from 'bootstrap/js/dist/tooltip';

export default class extends Controller {
    connect() {
        this.element.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
            new Tooltip(element, {
                container: this.element,
            });
        });
    }
}
