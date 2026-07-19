import { Controller } from '@hotwired/stimulus';

/**
 * Copies a code snippet to the clipboard.
 *
 * Shipped as plain ES modules (no build): a host serves this file through AssetMapper.
 */
export default class extends Controller {
    static targets = ['source', 'button'];
    static values = { copiedLabel: { type: String, default: 'Copied!' } };

    async copy() {
        await navigator.clipboard.writeText(this.sourceTarget.textContent);

        if (this.hasButtonTarget) {
            const previous = this.buttonTarget.textContent;
            this.buttonTarget.textContent = this.copiedLabelValue;
            setTimeout(() => {
                this.buttonTarget.textContent = previous;
            }, 2000);
        }
    }
}
