import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['copyIcon', 'copiedIcon', 'favoriteIcon'];
    static values = { copiedDuration: { type: Number, default: 2000 } };

    #copiedTimeout = null;

    async copy(event) {
        await navigator.clipboard.writeText(event.params.text);

        this.copyIconTarget.toggleAttribute('hidden', true);
        this.copiedIconTarget.toggleAttribute('hidden', false);

        clearTimeout(this.#copiedTimeout);
        this.#copiedTimeout = setTimeout(() => {
            this.copyIconTarget.toggleAttribute('hidden', false);
            this.copiedIconTarget.toggleAttribute('hidden', true);
        }, this.copiedDurationValue);
    }

    toggleFavorite() {
        const icon = this.favoriteIconTarget;
        icon.dataset.favorite = String(icon.dataset.favorite !== 'true');
    }

    disconnect() {
        clearTimeout(this.#copiedTimeout);
    }
}
