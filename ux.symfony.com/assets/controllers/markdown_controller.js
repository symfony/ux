import { Controller } from '@hotwired/stimulus';
import snarkdown from 'snarkdown';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['input', 'preview'];

    render(event) {
        const rendered = snarkdown(this.inputTarget.value);
        this.previewTarget.innerHTML = rendered;
    }
}
