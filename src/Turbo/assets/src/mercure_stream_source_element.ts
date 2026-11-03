import { connectStreamSource, disconnectStreamSource } from '@hotwired/turbo';

class TurboMercureStreamSourceElement extends HTMLElement {
    static observedAttributes = ['src', 'private', 'connected'];

    private es: EventSource | undefined;
    private closeTimeout: ReturnType<typeof setTimeout> | undefined;

    connectedCallback() {
        clearTimeout(this.closeTimeout);
        this.closeTimeout = undefined;

        if (this.es) {
            connectStreamSource(this.es);
        } else {
            this.open();
        }
    }

    disconnectedCallback() {
        if (this.es) {
            disconnectStreamSource(this.es);
        }

        this.closeTimeout = setTimeout(() => {
            this.closeTimeout = undefined;
            this.close();
        });
    }

    attributeChangedCallback(name: string) {
        if (!this.es) {
            return;
        }

        if ('connected' !== name) {
            this.close();
            if (this.isConnected) {
                this.open();
            }
        } else if (EventSource.OPEN === this.es.readyState && !this.hasAttribute('connected')) {
            // morphing the page removes the attribute, as the HTML rendered by the server doesn't have it
            this.setAttribute('connected', '');
        }
    }

    private open() {
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

    private close() {
        if (this.es) {
            disconnectStreamSource(this.es);
            this.es.close();
            this.es = undefined;
        }
        this.removeAttribute('connected');
    }
}

if (!customElements.get('turbo-mercure-stream-source')) {
    customElements.define('turbo-mercure-stream-source', TurboMercureStreamSourceElement);
}
