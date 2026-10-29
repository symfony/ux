import { connectStreamSource, disconnectStreamSource } from '@hotwired/turbo';

class TurboMercureStreamSourceElement extends HTMLElement {
    static observedAttributes = ['src', 'private', 'connected'];

    private es: EventSource | undefined;

    connectedCallback() {
        const src = this.getAttribute('src');
        if (null === src) {
            throw new Error('The "src" attribute is required on <turbo-mercure-stream-source>.');
        }

        this.es = new EventSource(src, {
            withCredentials: this.hasAttribute('private'),
        });
        connectStreamSource(this.es);
        this.es.addEventListener('open', () => this.setAttribute('connected', ''));
        this.es.addEventListener('error', () => this.removeAttribute('connected'));
    }

    disconnectedCallback() {
        if (this.es) {
            disconnectStreamSource(this.es);
            this.es.close();
            this.es = undefined;
        }
        this.removeAttribute('connected');
    }

    attributeChangedCallback(name: string) {
        if (!this.es) {
            return;
        }

        if ('connected' !== name) {
            this.disconnectedCallback();
            this.connectedCallback();
        } else if (EventSource.OPEN === this.es.readyState && !this.hasAttribute('connected')) {
            // morphing the page removes the attribute, as the HTML rendered by the server doesn't have it
            this.setAttribute('connected', '');
        }
    }
}

if (!customElements.get('turbo-mercure-stream-source')) {
    customElements.define('turbo-mercure-stream-source', TurboMercureStreamSourceElement);
}
