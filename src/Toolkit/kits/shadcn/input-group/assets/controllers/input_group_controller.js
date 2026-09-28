import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    focusInput(event) {
        if (event.target.closest('button')) {
            return;
        }

        event.currentTarget.parentElement?.querySelector('input')?.focus();
    }
}
