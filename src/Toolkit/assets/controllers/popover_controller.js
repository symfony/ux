import { Controller } from '@hotwired/stimulus';

/**
 * Toggles a popover (used for the "(?)" hints in API reference tables).
 *
 * Shipped as plain ES modules (no build): a host serves this file through AssetMapper.
 */
export default class extends Controller {
    static targets = ['content'];

    connect() {
        this.contentTarget.hidden = true;
        this._onOutsideClick = (event) => {
            if (!this.element.contains(event.target)) {
                this.contentTarget.hidden = true;
            }
        };
        document.addEventListener('click', this._onOutsideClick);
    }

    disconnect() {
        document.removeEventListener('click', this._onOutsideClick);
    }

    toggle() {
        this.contentTarget.hidden = !this.contentTarget.hidden;
    }
}
