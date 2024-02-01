import Clipboard from 'stimulus-clipboard'

/* stimulusFetch: 'lazy' */
export default class extends Clipboard {
    static values = {
        source: String,
    }

    /**
     * Overridden so we can simply pass the value in via a value.
     */
    copy(event) {
        if (!this.sourceValue) {
            super.copy(event);
        }

        event.preventDefault();

        navigator.clipboard.writeText(this.sourceValue).then(() => this.copied());
    }
}
