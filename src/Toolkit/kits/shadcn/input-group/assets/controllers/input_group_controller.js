import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    focusInput(event) {
        if (event.target.closest('button')) {
            return;
        }

        event.currentTarget.parentElement?.querySelector('input')?.focus();
    }
}
