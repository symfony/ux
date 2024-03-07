import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    reduce() {
        this.element.classList.toggle('Browser--reduced');
    }
}
