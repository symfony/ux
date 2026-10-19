import { Controller } from '@hotwired/stimulus';

/**
 * @value  copiedDuration  How long, in milliseconds, a copy button shows its "Copied" state.
 * @target content         The `pre` element holding the code.
 * @target status          The live region that announces a successful copy.
 * @action copy            Copies the `data-code-block-text` of the clicked button to the clipboard.
 * @action download        Downloads the `data-code-block-text` of the clicked button as a file named by its `data-code-block-filename-param`.
 * @action toggleWrap      Toggles the wrapping of long lines, and the `aria-pressed` state of the clicked button.
 * @action toggleExpanded  Toggles `data-expanded` on the content the clicked button controls, and its `aria-expanded` state.
 */
export default class extends Controller {
    static targets = ['content', 'status'];
    static values = { copiedDuration: { type: Number, default: 2000 } };

    #copiedTimeouts = new Map();
    #labels = new WeakMap();

    disconnect() {
        // Reset the buttons too, or one copied right before a disconnect would keep its "Copied" state, e.g. in a Turbo snapshot.
        for (const [button, timeout] of this.#copiedTimeouts) {
            clearTimeout(timeout);
            this.#setCopied(button, false);
        }
        this.#copiedTimeouts.clear();
    }

    async copy({ currentTarget: button }) {
        try {
            await navigator.clipboard.writeText(button.dataset.codeBlockText ?? '');
        } catch {
            return;
        }

        this.#setCopied(button, true);

        clearTimeout(this.#copiedTimeouts.get(button));
        this.#copiedTimeouts.set(
            button,
            setTimeout(() => {
                this.#copiedTimeouts.delete(button);
                this.#setCopied(button, false);
            }, this.copiedDurationValue)
        );
    }

    download({ currentTarget: button, params: { filename } }) {
        const url = URL.createObjectURL(new Blob([button.dataset.codeBlockText ?? ''], { type: 'text/plain' }));
        const link = document.createElement('a');
        link.href = url;
        link.download = String(filename ?? 'code.txt');
        link.click();
        // Revoked on the next task, so the browser has started the download first.
        setTimeout(() => URL.revokeObjectURL(url));
    }

    toggleWrap({ currentTarget: button }) {
        const wrap = button.getAttribute('aria-pressed') !== 'true';

        button.setAttribute('aria-pressed', String(wrap));
        for (const content of this.contentTargets) {
            content.toggleAttribute('data-wrap', wrap);
        }
    }

    toggleExpanded({ currentTarget: button }) {
        const expanded = button.getAttribute('aria-expanded') !== 'true';
        const controls = button.getAttribute('aria-controls');
        const content = this.contentTargets.find((target) => target.id === controls) ?? this.contentTarget;

        button.setAttribute('aria-expanded', String(expanded));
        content.toggleAttribute('data-expanded', expanded);
    }

    #setCopied(button, copied) {
        if (!this.#labels.has(button)) {
            this.#labels.set(button, button.getAttribute('aria-label') ?? 'Copy');
        }

        button.dataset.copied = String(copied);
        button.setAttribute('aria-label', copied ? 'Copied' : this.#labels.get(button));

        const status = button.nextElementSibling;
        if (status && this.statusTargets.includes(status)) {
            status.textContent = copied ? 'Copied' : '';
        }
    }
}
